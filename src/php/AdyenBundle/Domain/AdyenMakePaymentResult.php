<?php

namespace Frontastic\Payment\AdyenBundle\Domain;

use Kore\DataObject\DataObject;

class AdyenMakePaymentResult extends DataObject
{
    /** @var AdyenAction|null */
    public $action;

    /** @var string|null */
    public $resultCode;

    /** @var string|null */
    public $merchantReference;

    /** @var string|null */
    public $pspReference;

    /** @var string|null */
    public $refusalReason;

    /** @var AdyenPaymentDetail[] */
    public $details = [];

    /* We want to pass additional properties to the Frontend */
    public function __set($name, $value): void
    {
        $this->$name = $value;
    }

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
}
