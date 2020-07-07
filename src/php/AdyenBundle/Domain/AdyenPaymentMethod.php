<?php

namespace Frontastic\Payment\AdyenBundle\Domain;

use Kore\DataObject\DataObject;

class AdyenPaymentMethod extends DataObject
{
    /** @var string */
    public $type;

    /** @var string */
    public $name;
}
