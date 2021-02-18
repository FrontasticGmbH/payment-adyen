<?php

namespace Frontastic\Payment\AdyenBundle\Domain;

use Adyen\Client;
use Adyen\Service\Checkout;
use Frontastic\Common\CartApiBundle\Domain\Cart;
use Frontastic\Common\CartApiBundle\Domain\CartApi;
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

    /** @var array<string, string> */
    private $originKeys;

    /**
     * @param array<string, string> $originKeys
     */
    public function __construct(
        Client $adyenClient,
        UrlGeneratorInterface $router,
        CartApi $cartApi,
        string $clientKey,
        array $originKeys
    ) {
        $this->adyenClient = $adyenClient;
        $this->router = $router;
        $this->cartApi = $cartApi;
        $this->clientKey = $clientKey;
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
                'clientKey' => $this->clientKey,
            ],
        ]);
    }

    /**
     * @param array<mixed> $paymentMethod
     * @param array<mixed> $browserInfo
     */
    public function makePayment(
        Cart $cart,
        array $paymentMethod,
        array $browserInfo,
        Locale $locale,
        string $origin,
        ?string $clientIp
    ): AdyenPaymentResult {
        $paymentId = Uuid::uuid4()->toString();

        $payment = new Payment([
            'id' => $paymentId,
            'paymentProvider' => 'adyen',
            'amount' => $cart->sum,
            'currency' => $cart->currency,
            'paymentStatus' => Payment::PAYMENT_STATUS_INIT,
        ]);
        $this->cartApi->startTransaction($cart);
        $this->cartApi->addPayment($cart, $payment, null, $locale->toString());
        $cart = $this->cartApi->commit($locale->toString());

        $checkoutService = $this->buildCheckoutService();
        $paymentParameters = [
            'amount' => $this->buildCartAmount($cart),
            'reference' => $cart->cartId,
            'paymentMethod' => $paymentMethod,
            'browserInfo' => $browserInfo,
            'additionalData' => [
                'allow3DS2' => true,
            ],
            'channel' => 'Web',
            'origin' => $origin,
            'returnUrl' =>
                $origin . $this->router->generate(
                    'Frontastic.Adyen.paymentReturn',
                    [
                        'cartId' => $cart->cartId,
                        'paymentId' => $paymentId,
                    ]
                ),
        ];
        if ($clientIp !== null) {
            $paymentParameters['shopperIp'] = $clientIp;
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
}
