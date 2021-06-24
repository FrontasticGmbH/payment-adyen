<?php

namespace Frontastic\Payment\AdyenBundle\Domain;

use Kore\DataObject\DataObject;

/***
 * @type
 */
class AdyenPaymentMethodsResult extends DataObject
{
    /** @var AdyenPaymentMethod[] */
    public $paymentMethods;

    /** @var array<mixed> */
    public $configuration;
}
