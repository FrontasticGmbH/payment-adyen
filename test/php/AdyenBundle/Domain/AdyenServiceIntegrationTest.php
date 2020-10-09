<?php

namespace Frontastic\Payment\AdyenBundle\Domain;

use Frontastic\Common\CartApiBundle\Domain\Cart;
use Frontastic\Common\CartApiBundle\Domain\CartApi;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Locale;
use Frontastic\Common\ReplicatorBundle\Domain\Project;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AdyenServiceIntegrationTest extends TestCase
{
    /** @var AdyenService */
    private $adyenService;

    /**
     * @before
     */
    public function setupService()
    {
        $router = $this->getMockBuilder(UrlGeneratorInterface::class)->getMock();
        $cartApi = $this->getMockBuilder(CartApi::class)->getMock();
        $serviceFactory = new AdyenServiceFactory($router, $cartApi);
        $project = new Project([
            'configuration' => [
                'payment' => [
                    'adyen' => [
                        'apiKey' => 'AQEphmfuXNWTK0Qc+iSWgGs2s+WIXIJOKp5IcJtsjzAUli2Gh6PoLfl2xhUQwV1bDb7kfNy1WIxIIkxgBw==-gkoNSuhoc77v0ZG0rVeS53otT9bqL1sbjaGm6s2DyCQ=-}f<gG4qM~q(F6#dK',
                        'merchantAccount' => 'FrontasticGmbHECOM',
                        'clientKey' => 'client key',
                        'originKeys' => [
                            'http://some.domain' => 'key 1',
                            'https://other.domain' => 'key 2',
                        ],
                    ],
                ],
            ],
        ]);

        $this->adyenService = $serviceFactory->factorForProject($project);
    }

    public function testFetchPaymentForCart(): void
    {
        $cart = new Cart([
            'sum' => 19995,
            'currency' => 'EUR',
        ]);
        $locale = Locale::createFromPosix('de_DE');

        $paymentMethodsResult = $this->adyenService->fetchPaymentMethodsForCart($cart, $locale, 'https://other.domain');

        $this->assertInstanceOf(AdyenPaymentMethodsResult::class, $paymentMethodsResult);

        $this->assertIsArray($paymentMethodsResult->paymentMethods);
        $this->assertNotEmpty($paymentMethodsResult->paymentMethods);
        foreach ($paymentMethodsResult->paymentMethods as $paymentMethod) {
            $this->assertInstanceOf(AdyenPaymentMethod::class, $paymentMethod);
            $this->assertIsString($paymentMethod->type);
            $this->assertIsString($paymentMethod->name);

            $this->assertNotEmpty($paymentMethod->type);
            $this->assertNotEmpty($paymentMethod->name);
        }

        $this->assertIsArray($paymentMethodsResult->configuration);
        $this->assertIsArray($paymentMethodsResult->configuration['paymentMethodsResponse']);
        $this->assertSame('de-DE', $paymentMethodsResult->configuration['locale']);
        $this->assertSame('test', $paymentMethodsResult->configuration['environment']);
        $this->assertSame('key 2', $paymentMethodsResult->configuration['originKey']);
    }

    public function testThrowExceptionOnInvalidOrigin(): void
    {
        $cart = new Cart([
            'sum' => 19995,
            'currency' => 'EUR',
        ]);
        $locale = Locale::createFromPosix('de_DE');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown Adyen origin: https://wrong.domain');

        $this->adyenService->fetchPaymentMethodsForCart($cart, $locale, 'https://wrong.domain');
    }

}
