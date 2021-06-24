<?php

namespace Frontastic\Payment\AdyenBundle\Domain;

use Kore\DataObject\DataObject;

/***
 * @type
 */
class AdyenPaymentMethod extends DataObject
{
    /** @var string */
    public $type;

    /** @var string */
    public $name;
}
