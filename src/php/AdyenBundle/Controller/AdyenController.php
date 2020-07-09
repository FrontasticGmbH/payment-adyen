<?php

namespace Frontastic\Payment\AdyenBundle\Controller;

use Frontastic\Catwalk\ApiCoreBundle\Domain\Context;
use Frontastic\Common\CartApiBundle\Controller\CartController;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Locale;
use Frontastic\Payment\AdyenBundle\Domain\AdyenMakePaymentResult;
use Frontastic\Payment\AdyenBundle\Domain\AdyenPaymentMethodsResult;
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
            Locale::createFromPosix($context->locale),
            $request->getSchemeAndHttpHost()
        );
    }

    public function makePaymentAction(Context $context, Request $request): AdyenMakePaymentResult
    {
        /** @var AdyenService $adyenService */
        $adyenService = $this->get(AdyenService::class);

        $body = $this->getJsonContent($request);
        if (!is_array($body['paymentMethod'] ?? null)) {
            throw new BadRequestHttpException('Missing object paymentMethod in JSON body');
        }

        $paymentResult = $adyenService->makePayment(
            $this->getCart($context, $request),
            $body['paymentMethod'],
            $request->getSchemeAndHttpHost()
        );

        if ($paymentResult->action !== null && $paymentResult->action->isRedirect() && $request->hasSession()) {
            $session = $request->getSession();
            $session->set('adyen_payment_data', $paymentResult->action->paymentData);
            $session->set('adyen_detail_keys', $paymentResult->getDetailKeys());
        }

        return $paymentResult;
    }

    public function paymentReturnAction(Context $context, Request $request): RedirectRouteResponse
    {
        /** @var AdyenService $adyenService */
        $adyenService = $this->get(AdyenService::class);

        if (!$request->hasSession()) {
            throw new \RuntimeException('Adyen payment return needs a session');
        }

        $session = $request->getSession();
        $paymentData = $session->get('adyen_payment_data');
        $detailKeys = $session->get('adyen_detail_keys');

        $details = [];
        foreach ($detailKeys as $detailKey) {
            $details[$detailKey] = $request->get($detailKey);
        }

        $result = $adyenService->submitPaymentDetails($details, $paymentData);

        return new RedirectRouteResponse(
            'Frontastic.Frontend.Master.Checkout.checkout',
            [
                'adyen' => 'redirect',
            ]
        );
    }
}
