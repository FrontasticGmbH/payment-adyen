<?php

namespace Frontastic\Payment\AdyenBundle\Controller;

use Frontastic\Catwalk\ApiCoreBundle\Domain\Context;
use Frontastic\Common\CartApiBundle\Controller\CartController;
use Frontastic\Common\CartApiBundle\Domain\CartApi;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Locale;
use Frontastic\Payment\AdyenBundle\Domain\AdyenPaymentMethodsResult;
use Frontastic\Payment\AdyenBundle\Domain\AdyenPaymentResult;
use Frontastic\Payment\AdyenBundle\Domain\AdyenService;
use QafooLabs\MVC\RedirectRouteResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AdyenController extends CartController
{
    public function getPaymentMethodsAction(Context $context, Request $request): AdyenPaymentMethodsResult
    {
        /** @var AdyenService $adyenService */
        $adyenService = $this->get(AdyenService::class);

        return $adyenService->fetchPaymentMethodsForCart(
            $this->getCart($context, $request),
            $this->getLocaleForContext($context),
            $this->getOriginForRequest($request)
        );
    }

    public function makePaymentAction(Context $context, Request $request): AdyenPaymentResult
    {
        /** @var AdyenService $adyenService */
        $adyenService = $this->get(AdyenService::class);

        $body = $this->getJsonContent($request);
        if (!is_array($body['paymentMethod'] ?? null)) {
            throw new BadRequestHttpException('Missing object paymentMethod in JSON body');
        }
        if (!is_array($body['browserInfo'] ?? null)) {
            throw new BadRequestHttpException('Missing object browserInfo in JSON body');
        }

        return $adyenService->makePayment(
            $this->getCart($context, $request),
            $body['paymentMethod'],
            $body['browserInfo'],
            $this->getLocaleForContext($context),
            $this->getOriginForRequest($request)
        );
    }

    public function paymentReturnAction(
        Context $context,
        Request $request,
        string $cartId,
        string $paymentId
    ): RedirectRouteResponse {
        /** @var AdyenService $adyenService */
        $adyenService = $this->get(AdyenService::class);

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

        $adyenService->submitPaymentDetails(
            $cart,
            $paymentId,
            $details,
            $paymentData,
            $this->getLocaleForContext($context)
        );

        return new RedirectRouteResponse(
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
