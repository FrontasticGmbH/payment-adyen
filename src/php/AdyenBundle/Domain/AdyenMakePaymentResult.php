<?php

namespace Frontastic\Payment\AdyenBundle\Domain;

use Kore\DataObject\DataObject;

class AdyenMakePaymentResult extends DataObject
{
    /** @var array<mixed>|null */
    public $action;

    /** @var string|null */
    public $resultCode;

    /** @var string|null */
    public $refusalReason;
}
