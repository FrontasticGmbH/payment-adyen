<?php

namespace Frontastic\Payment\AdyenBundle\Domain;

use Kore\DataObject\DataObject;

class AdyenPaymentMethodsResult extends DataObject
{
    /** @var AdyenPaymentMethod[] */
    public $paymentMethods;

    /** @var array<mixed> */
    public $configuration;
}
