<?php

namespace Frontastic\Payment\AdyenBundle\Domain;

use Kore\DataObject\DataObject;

class AdyenAction extends DataObject
{
    public const TYPE_REDIRECT = 'redirect';

    /**
     * @var string
     */
    public $type;

    /**
     * @var string|null
     */
    public $paymentData;

    public function isRedirect(): bool
    {
        return $this->type === self::TYPE_REDIRECT;
    }

    /* We want to pass additional properties to the Frontend */
    public function __set($name, $value): void
    {
        $this->$name = $value;
    }
}
