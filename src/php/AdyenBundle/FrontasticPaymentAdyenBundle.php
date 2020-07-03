<?php

namespace Frontastic\Payment\AdyenBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class FrontasticPaymentAdyenBundle extends Bundle
{
    public function getParent(): ?string
    {
        return 'FrontasticPaymentAdyenBundle';
    }
}
