<?php

namespace Frontastic\Payment\AdyenBundle\Controller;

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Frontastic\Catwalk\ApiCoreBundle\Domain\Context;
use Frontastic\Catwalk\FrontendBundle\Controller\CartController;
use Frontastic\Catwalk\FrontendBundle\Controller\CartFetcher;
use Frontastic\Catwalk\TrackingBundle\Domain\TrackingService;
use Frontastic\Common\CartApiBundle\Domain\CartApi;
use Frontastic\Common\CartApiBundle\Domain\CartApiFactory;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Locale;
use Frontastic\Payment\AdyenBundle\Domain\AdyenPaymentMethodsResult;
use Frontastic\Payment\AdyenBundle\Domain\AdyenService;
use Gyro\MVC\RedirectRoute;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @IgnoreAnnotation("Docs\Request")
 * @IgnoreAnnotation("Docs\Response")
 */
class AdyenController extends CartController
{
    private AdyenService $adyenService;

    public function __construct(
        AdyenService $adyenService,
        TrackingService $trackingService,
        CartApi $cartApiService,
        CartFetcher $cartFetcher,
        LoggerInterface $logger,
        CartApiFactory $cartApiFactory
    ) {
        $this->adyenService =  $adyenService;

        parent::__construct(
            $trackingService,
            $cartApiService,
            $cartFetcher,
            $logger,
            $cartApiFactory
        );
    }

    /**
     * Get available payment methods
     *
     * @Docs\Request(
     *  "GET",
     *  "/api/payment/adyen/paymentsMethod"
     * )
     * @Docs\Response(
     *  "200",
     *  "AdyenPaymentMethodsResult"
     * )
     */
    public function getPaymentMethodsAction(Context $context, Request $request): AdyenPaymentMethodsResult
    {
        return $this->adyenService->fetchPaymentMethodsForCart(
            $this->getCart($context, $request),
            $this->getLocaleForContext($context),
            $this->getOriginForRequest($request)
        );
    }

    /**
     * Make payment
     *
     * @Docs\Request(
     *  "POST",
     *  "/api/payment/adyen/payment",
     *  "object{
     *    paymentMethod: \Frontastic\Payment\AdyenBundle\Domain\AdyenPaymentMethod,
     *    browserInfo: ?object,
     *    shopperReference: ?string
     *  }"
     * )
     * @Docs\Response(
     *  "200",
     *  "\Frontastic\Payment\AdyenBundle\Domain\AdyenPaymentResult"
     * )
     */
    public function makePaymentAction(Context $context, Request $request): JsonResponse
    {
        $body = $this->getJsonContent($request);
        if (!is_array($body['paymentMethod'] ?? null)) {
            throw new BadRequestHttpException('Missing object paymentMethod in JSON body');
        }

        return new JsonResponse($this->adyenService->makePayment(
            $this->getCart($context, $request),
            $body['paymentMethod'],
            $body['browserInfo'] ?? null,
            $this->getLocaleForContext($context),
            $this->getOriginForRequest($request),
            $request->getClientIp(),
            $body['shopperReference'] ?? null
        ));
    }

    public function addidionalPaymentDetailsAction(Context $context, Request $request, string $paymentId): JsonResponse
    {
        $body = $this->getJsonContent($request);
        if (!is_array($body['details'] ?? null)) {
            throw new BadRequestHttpException('Missing object details in JSON body');
        }
        if (!is_string($body['paymentData'] ?? null)) {
            throw new BadRequestHttpException('Missing string paymentData in JSON body');
        }

        $cart = $this->getCart($context, $request);

        return new JsonResponse($this->adyenService->submitPaymentDetails(
            $cart,
            $paymentId,
            $body['details'],
            $body['paymentData'],
            $this->getLocaleForContext($context)
        ));
    }

    public function paymentReturnAction(
        Context $context,
        Request $request,
        string $cartId,
        string $paymentId
    ): RedirectRoute {
        /** @var CartApi $cartApi */
        $cartApi = $this->get('frontastic.catwalk.cart_api');

        $cart = $cartApi->getById($cartId, $context->locale);
        $payment = $cart->getPaymentById($paymentId);

        $paymentData = $payment->paymentDetails['adyenPaymentData'] ?? null;
        $detailKeys = $payment->paymentDetails['adyenDetailKeys'] ?? null;

        if ($paymentData === null || $detailKeys === null) {
            throw new \RuntimeException('Payment has no payment data or no detail keys');
        }

        $details = [];
        foreach ($detailKeys as $detailKey) {
            $details[$detailKey] = $request->get($detailKey);
        }

        $this->adyenService->submitPaymentDetails(
            $cart,
            $paymentId,
            $details,
            $paymentData,
            $this->getLocaleForContext($context)
        );

        return new RedirectRoute(
            'Frontastic.Frontend.Master.Checkout.checkout',
            [
                'adyenPaymentId' => $paymentId,
            ]
        );
    }

    private function getOriginForRequest(Request $request): string
    {
        return $request->query->get('origin', $request->getSchemeAndHttpHost());
    }

    private function getLocaleForContext(Context $context): Locale
    {
        return Locale::createFromPosix($context->locale);
    }
}
