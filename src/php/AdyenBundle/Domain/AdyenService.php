<?php

namespace Frontastic\Payment\AdyenBundle\Domain;

use Adyen\Client;
use Adyen\Service\Checkout;
use Frontastic\Common\AccountApiBundle\Domain\Address;
use Frontastic\Common\CartApiBundle\Domain\Cart;
use Frontastic\Common\CartApiBundle\Domain\CartApi;
use Frontastic\Common\CartApiBundle\Domain\LineItem;
use Frontastic\Common\CartApiBundle\Domain\Payment;
use Frontastic\Common\ProductApiBundle\Domain\ProductApi\Locale;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AdyenService
{
    /** @var Client */
    private $adyenClient;

    /** @var UrlGeneratorInterface */
    private $router;

    /** @var CartApi */
    private $cartApi;

    /** @var string */
    private $clientKey;

    /** @var string */
    private $environemnt;

    /** @var array<string, string> */
    private $originKeys;

    /** @var array<string, string> */
    private $additionalPaymentConfig;

    /**
     * @param array<string, string> $originKeys
     * @param array<string, string> $additionalPaymentConfig
     */
    public function __construct(
        Client $adyenClient,
        UrlGeneratorInterface $router,
        CartApi $cartApi,
        string $clientKey,
        string $environment,
        array $originKeys,
        array $additionalPaymentConfig = []
    ) {
        $this->adyenClient = $adyenClient;
        $this->router = $router;
        $this->cartApi = $cartApi;
        $this->clientKey = $clientKey;
        $this->environemnt = $environment;
        $this->originKeys = $originKeys;
        $this->additionalPaymentConfig = $additionalPaymentConfig;
    }

    public function fetchPaymentMethodsForCart(Cart $cart, Locale $locale, string $origin): AdyenPaymentMethodsResult
    {
        $checkoutService = $this->buildCheckoutService();
        $adyenLocale = $this->buildAdyenLocale($locale);
        $amount = $this->buildCartAmount($cart);
        $result = $checkoutService->paymentMethods([
            'countryCode' => $locale->territory,
            'shopperLocale' => $adyenLocale,
            'amount' => $amount,
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
            'configuration' => array_merge(
                [
                    'paymentMethodsResponse' => $result,
                    'originKey' => $this->getOriginKey($origin),
                    'locale' => $adyenLocale,
                    'environment' => $this->environemnt,
                    'clientKey' => $this->clientKey,
                    'hasHolderName' => true,
                    'amount' => $amount,
                ],
                $this->additionalPaymentConfig
            ),
        ]);
    }

    /**
     * @param array<mixed> $paymentMethod
     * @param array<mixed>|null $browserInfo
     */
    public function makePayment(
        Cart $cart,
        array $paymentMethod,
        ?array $browserInfo,
        Locale $locale,
        string $origin,
        ?string $clientIp,
        ?string $shopperReference
    ): AdyenPaymentResult {
        $paymentId = Uuid::uuid4()->toString();

        $payment = new Payment([
            'id' => $paymentId,
            'paymentProvider' => 'adyen',
            'amount' => $cart->sum,
            'currency' => $cart->currency,
            'paymentStatus' => Payment::PAYMENT_STATUS_INIT,
            'paymentMethod' => $paymentMethod['type'] ?? null
        ]);
        $this->cartApi->startTransaction($cart);
        $this->cartApi->addPayment($cart, $payment, null, $locale->toString());
        $cart = $this->cartApi->commit($locale->toString());

        $checkoutService = $this->buildCheckoutService();
        $paymentParameters = [
            'countryCode' => $locale->territory,
            'shopperLocale' => $this->buildAdyenLocale($locale),
            'amount' => $this->buildCartAmount($cart),
            'reference' => $cart->cartId,
            'paymentMethod' => $paymentMethod,
            'browserInfo' => $browserInfo,
            'additionalData' => [
                'allow3DS2' => true,
            ],
            'lineItems' => $this->buildAdyenLineItems($cart),
            'channel' => 'Web',
            'origin' => $origin,
            'returnUrl' =>
                $origin . $this->router->generate(
                    'Frontastic.Adyen.paymentReturn',
                    [
                        'cartId' => $cart->cartId,
                        'paymentId' => $paymentId,
                        session_name() => session_id(),
                    ]
                ),
        ];

        if ($cart->hasUser()) {
            $paymentParameters['shopperEmail'] = $cart->email;
        }

        if ($cart->hasBillingAddress()) {
            $paymentParameters['shopperName'] = [
                // @phpstan-ignore-next-line hasBillingAddress() is false if the first name null
                'firstName' => $cart->billingAddress->firstName,
                // @phpstan-ignore-next-line hasBillingAddress() is false if the last name null
                'lastName' => $cart->billingAddress->lastName,
            ];
            // @phpstan-ignore-next-line hasBillingAddress() is false if the address is null
            $paymentParameters['billingAddress'] = $this->buildAdyenAddress($cart->billingAddress);
        }

        if ($cart->hasShippingAddress()) {
            // @phpstan-ignore-next-line hasShippingAddress() is false if the address is null
            $paymentParameters['deliveryAddress'] = $this->buildAdyenAddress($cart->shippingAddress);
        }

        if ($clientIp !== null) {
            $paymentParameters['shopperIP'] = $clientIp;
        }

        if ($shopperReference !== null) {
            $paymentParameters['shopperReference'] = $shopperReference;
        }

        $result = $checkoutService->payments($paymentParameters);
        $paymentResult = $this->buildPaymentResult($result, $paymentId);
        $this->updatePaymentWithResult($paymentResult, $cart, $paymentId, $locale);

        return $paymentResult;
    }

    /**
     * @param array<mixed> $details
     */
    public function submitPaymentDetails(
        Cart $cart,
        string $paymentId,
        array $details,
        string $paymentData,
        Locale $locale
    ): AdyenPaymentResult {
        $checkoutService = $this->buildCheckoutService();
        $result = $checkoutService->paymentsDetails([
            'details' => $details,
            'paymentData' => $paymentData,
        ]);
        $paymentResult = $this->buildPaymentResult($result, $paymentId);
        $this->updatePaymentWithResult($paymentResult, $cart, $paymentId, $locale);

        return $paymentResult;
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

    private function updatePaymentWithResult(
        AdyenPaymentResult $paymentResult,
        Cart $cart,
        string $paymentId,
        Locale $locale
    ): void {
        $payment = $cart->getPaymentById($paymentId);

        switch ($paymentResult->resultCode) {
            case 'Authorised':
                $payment->paymentStatus = Payment::PAYMENT_STATUS_PAID;
                break;
            case 'Error':
            case 'Refused':
                $payment->paymentStatus = Payment::PAYMENT_STATUS_FAILED;
                break;
            default:
                $payment->paymentStatus = Payment::PAYMENT_STATUS_PENDING;
                break;
        }

        if ($paymentResult->pspReference !== null) {
            $payment->paymentId = $paymentResult->pspReference;
        }

        if ($payment->paymentDetails === null) {
            $payment->paymentDetails = [];
        }

        $payment->paymentDetails['adyenResultCode'] = $paymentResult->resultCode;

        if ($paymentResult->action !== null) {
            $payment->paymentDetails['adyenAction'] = $paymentResult->action;
        } else {
            unset($payment->paymentDetails['adyenAction']);
        }

        if ($paymentResult->action !== null && $paymentResult->action->isRedirect()) {
            $payment->paymentDetails['adyenPaymentData'] = $paymentResult->action->paymentData;
            $payment->paymentDetails['adyenDetailKeys'] = $paymentResult->getDetailKeys();
        } else {
            unset($payment->paymentDetails['adyenPaymentData']);
            unset($payment->paymentDetails['adyenDetailKeys']);
        }

        $this->cartApi->updatePayment($cart, $payment, $locale->toString());
    }

    /**
     * @param array<mixed> $result
     */
    private function buildPaymentResult(array $result, string $paymentId): AdyenPaymentResult
    {
        if (array_key_exists('action', $result)) {
            $action = new AdyenAction($result['action']);

            if ($action->isRedirect() && $action->paymentData === null) {
                throw new \RuntimeException('Action type redirect needs paymentData');
            }

            $result['action'] = $action;
        }

        if (array_key_exists('details', $result)) {
            $details = array_map(
                function (array $paymentDetail): AdyenPaymentDetail {
                    return new AdyenPaymentDetail($paymentDetail);
                },
                $result['details']
            );
            $result['details'] = $details;
        }

        $paymentResult = new AdyenPaymentResult($result);
        $paymentResult->paymentId = $paymentId;
        return $paymentResult;
    }

    /**
     * @return array<string, mixed>[]
     */
    private function buildAdyenLineItems(Cart $cart): array
    {
        return array_map(
            function (LineItem $lineItem) {
                return [
                    'id' => $lineItem->lineItemId,
                    'description' => $lineItem->name,
                    'quantity' => $lineItem->count,
                    'amountIncludingTax' => $lineItem->totalPrice,
                ];
            },
            $cart->lineItems
        );
    }

    /**
     * @return array<string, string>
     */
    private function buildAdyenAddress(Address $address): array
    {
        return [
            'country' => $address->country,
            'city' => $address->city,
            'street' => $address->streetName ?? '',
            'houseNumberOrName' => $address->streetNumber ?? '',
            'postalCode' => $address->postalCode,
            'stateOrProvince' => $address->state ?? '',
        ];
    }
}
