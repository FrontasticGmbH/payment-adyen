<?php

namespace Frontastic\Payment\AdyenBundle\Domain;

use Adyen\Client;
use Adyen\Environment;
use Frontastic\Common\CartApiBundle\Domain\CartApi;
use Frontastic\Common\ReplicatorBundle\Domain\Project;
use Frontastic\Payment\AdyenBundle\Domain\Exception\ConfigurationParameterMissingException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AdyenServiceFactory
{
    /** @var UrlGeneratorInterface */
    private $router;

    /** @var CartApi */
    private $cartApi;

    public function __construct(UrlGeneratorInterface $router, CartApi $cartApi)
    {
        $this->router = $router;
        $this->cartApi = $cartApi;
    }

    public function factorForProject(Project $project): AdyenService
    {
        /** @var \stdClass $adyenConfig */
        $adyenConfig = $project->getConfigurationSection('payment', 'adyen');

        $environment = self::getStringOption($adyenConfig, 'environment', Environment::TEST);

        $liveUrlPrefix = null;
        if ($environment === Environment::LIVE) {
            $liveUrlPrefix = self::getStringOption($adyenConfig, 'liveUrlPrefix');
        }

        $client = new Client();
        $client->setXApiKey(self::getStringOption($adyenConfig, 'apiKey'));
        $client->setMerchantAccount(self::getStringOption($adyenConfig, 'merchantAccount'));
        $client->setEnvironment($environment, $liveUrlPrefix);

        return new AdyenService(
            $client,
            $this->router,
            $this->cartApi,
            self::getStringOption($adyenConfig, 'clientKey'),
            self::getStringMapOption($adyenConfig, 'originKeys', true),
            self::getStringMapOption($adyenConfig, 'additionalPaymentConfig', false)
        );
    }

    private static function getStringOption(\stdClass $config, string $option, ?string $default = null): string
    {
        $value = $config->$option ?? $default;
        if ($value === null) {
            throw new \RuntimeException('Adyen config option ' . $option . ' is not set');
        }
        if (!is_string($value)) {
            throw new \RuntimeException('Adyen config option ' . $option . ' is no string');
        }
        if ($value === '') {
            throw new \RuntimeException('Adyen config option ' . $option . ' is empty');
        }

        return $value;
    }

    /**
     * @return array<string, string>
     */
    private static function getStringMapOption(\stdClass $config, string $option, bool $required): array
    {
        $map = $config->$option ?? ($required ? null : []);
        if ($map === null) {
            throw new \RuntimeException('Adyen config option ' . $option . ' is not set');
        }
        if (is_object($map)) {
            $map = (array)$map;
        }
        if (!is_array($map)) {
            throw new \RuntimeException('Adyen config option ' . $option . ' is no object');
        }

        foreach ($map as $key => $value) {
            if (!is_string($key)) {
                throw new \RuntimeException('Adyen config option ' . $option . ' needs to have string keys');
            }
            if ($key === '') {
                throw new \RuntimeException('Adyen config option ' . $option . ' may not have empty keys');
            }
            if (!is_string($value)) {
                throw new \RuntimeException('Adyen config option ' . $option . ' needs to have string values');
            }
            if ($value === '') {
                throw new \RuntimeException('Adyen config option ' . $option . ' may not have empty values');
            }
        }

        return $map;
    }
}
