<?php

namespace Frontastic\Payment\AdyenBundle\Domain;

use Kore\DataObject\DataObject;

/**
 * @type
 */
class AdyenPaymentResult extends DataObject
{
    /** @var string */
    public $paymentId;

    /** @var ?string */
    public $resultCode;

    /** @var ?string */
    public $merchantReference;

    /** @var ?string */
    public $pspReference;

    /** @var ?string */
    public $refusalReason;

    /** @var ?AdyenAction */
    public $action;

    /** @var AdyenPaymentDetail[] */
    public $details = [];

    /**
     * @return string[]
     */
    public function getDetailKeys(): array
    {
        return array_map(
            function (AdyenPaymentDetail $paymentDetail): string {
                return $paymentDetail->key;
            },
            $this->details
        );
    }

    /* We want to pass additional properties to the Frontend */
    public function __set($name, $value): void
    {
        $this->$name = $value;
    }
}
