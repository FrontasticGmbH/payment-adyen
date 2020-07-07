<?php

namespace Frontastic\Payment\AdyenBundle\Controller;

use Frontastic\Catwalk\ApiCoreBundle\Domain\Context;
use Frontastic\Common\CartApiBundle\Controller\CartController;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Locale;
use Frontastic\Payment\AdyenBundle\Domain\AdyenMakePaymentResult;
use Frontastic\Payment\AdyenBundle\Domain\AdyenPaymentMethodsResult;
use Frontastic\Payment\AdyenBundle\Domain\AdyenService;
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

        return $adyenService->makePayment(
            $this->getCart($context, $request),
            $body['paymentMethod'],
            $request->getSchemeAndHttpHost()
        );
    }
}
