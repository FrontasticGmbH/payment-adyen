<?php

namespace Frontastic\Payment\AdyenBundle\Domain;

use Kore\DataObject\DataObject;

class AdyenPaymentResult extends DataObject
{
    /** @var string|null */
    public $resultCode;

    /** @var string|null */
    public $merchantReference;

    /** @var string|null */
    public $pspReference;

    /** @var string|null */
    public $refusalReason;

    /** @var AdyenAction|null */
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
