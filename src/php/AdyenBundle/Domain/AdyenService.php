<?php

namespace Frontastic\Payment\AdyenBundle\Domain;

use Adyen\Client;
use Adyen\Service\Checkout;
use Frontastic\Common\CartApiBundle\Domain\Cart;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Locale;

class AdyenService
{
    /** @var Client */
    private $adyenClient;

    /** @var array<string, string> */
    private $originKeys;

    /**
     * @param array<string, string> $originKeys
     */
    public function __construct(Client $adyenClient, array $originKeys)
    {
        $this->adyenClient = $adyenClient;
        $this->originKeys = $originKeys;
    }

    public function fetchPaymentMethodsForCart(Cart $cart, Locale $locale, string $origin): AdyenPaymentMethodsResult
    {
        $checkoutService = $this->buildCheckoutService();
        $adyenLocale = $this->buildAdyenLocale($locale);
        $result = $checkoutService->paymentMethods([
            'countryCode' => $locale->territory,
            'shopperLocale' => $adyenLocale,
            'amount' => $this->buildCartAmount($cart),
            'channel' => 'Web',
        ]);

        $paymentMethods = array_map(
            function (array $methodData): AdyenPaymentMethod {
                return new AdyenPaymentMethod([
                    'type' => $methodData['type'],
                    'name' => $methodData['name'],
                ]);
            },
            $result['paymentMethods']
        );

        return new AdyenPaymentMethodsResult([
            'paymentMethods' => $paymentMethods,
            'configuration' => [
                'paymentMethodsResponse' => $result,
                'originKey' => $this->getOriginKey($origin),
                'locale' => $adyenLocale,
                'environment' => 'test',
            ],
        ]);
    }

    /**
     * @param array<mixed> $paymentMethod
     */
    public function makePayment(Cart $cart, array $paymentMethod, string $origin): AdyenMakePaymentResult
    {
        $checkoutService = $this->buildCheckoutService();
        $result = $checkoutService->payments([
            'amount' => $this->buildCartAmount($cart),
            'reference' => $cart->cartId,
            'paymentMethod' => $paymentMethod,
        ]);

        return new AdyenMakePaymentResult($result, true);
    }

    private function buildCheckoutService(): Checkout
    {
        return new Checkout($this->adyenClient);
    }

    private function buildAdyenLocale(Locale $locale): string
    {
        return sprintf('%s-%s', $locale->language, $locale->territory);
    }

    /**
     * @return array<mixed>
     */
    private function buildCartAmount(Cart $cart): array
    {
        return [
            'currency' => $cart->currency,
            'value' => $cart->sum,
        ];
    }

    private function getOriginKey(string $origin): string
    {
        if (!array_key_exists($origin, $this->originKeys)) {
            throw new \RuntimeException('Unknown Adyen origin: ' . $origin);
        }
        $originKey = $this->originKeys[$origin];
        return $originKey;
    }
}
