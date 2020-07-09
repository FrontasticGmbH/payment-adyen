<?php

namespace Frontastic\Payment\AdyenBundle\Domain;

use Kore\DataObject\DataObject;

class AdyenPaymentDetail extends DataObject
{
    /** @var string */
    public $key;

    /* We want to pass additional properties to the Frontend */
    public function __set($name, $value): void
    {
        $this->$name = $value;
    }
}
