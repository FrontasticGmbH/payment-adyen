<?php

namespace Frontastic\Payment\AdyenBundle\Domain;

use Adyen\Client;
use Adyen\Environment;
use Frontastic\Common\ReplicatorBundle\Domain\Project;

class AdyenServiceFactory
{
    public function factorForProject(Project $project): AdyenService
    {
        /** @var \stdClass $adyenConfig */
        $adyenConfig = $project->getConfigurationSection('payment', 'adyen');

        $client = new Client();
        $client->setXApiKey(self::getStringOption($adyenConfig, 'apiKey'));
        $client->setMerchantAccount(self::getStringOption($adyenConfig, 'merchantAccount'));
        $client->setEnvironment(Environment::TEST);

        return new AdyenService($client, self::getStringMapOption($adyenConfig, 'originKeys'));
    }

    private static function getStringOption(\stdClass $config, string $option): string
    {
        $value = $config->$option ?? null;
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
    private static function getStringMapOption(\stdClass $config, string $option): array
    {
        $map = $config->$option ?? null;
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
