<?php

namespace Frontastic\Payment\AdyenBundle\Controller;

use Frontastic\Catwalk\ApiCoreBundle\Domain\Context;
use Frontastic\Common\CartApiBundle\Controller\CartController;
use Frontastic\Payment\AdyenBundle\Domain\AdyenService;

class AdyenController extends CartController
{
    /**
     * @return array<mixed>
     */
    public function getPaymentMethodsAction(Context $context): array
    {
        $adyenService = $this->get(AdyenService::class);
        return [];
    }
}
