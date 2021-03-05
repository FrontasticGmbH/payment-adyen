<?php

namespace Frontastic\Payment\AdyenBundle\Domain\Exception;

use Throwable;

class ConfigurationParameterMissingException extends \Exception
{
    public function __construct(string $configurationParameterMissing, $code = 0, Throwable $previous = null)
    {
        $message = sprintf(
            'Mandatory configuration parameter "%s" missing! Did you forget to add it to the corresponding'
            . ' project.yml?',
            $configurationParameterMissing
        );

        parent::__construct($message, $code, $previous);
    }
}
