<?php

namespace Frontastic\Payment\AdyenBundle\Domain;

use Kore\DataObject\DataObject;

class AdyenPaymentDetailResult extends DataObject
{
    /** @var string|null */
    public $resultCode;

    /** @var string|null */
    public $merchantReference;

    /** @var string|null */
    public $pspReference;

    /* We want to pass additional properties to the Frontend */
    public function __set($name, $value): void
    {
        $this->$name = $value;
    }
}
