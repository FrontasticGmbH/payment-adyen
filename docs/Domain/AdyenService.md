#  AdyenService

**Fully Qualified**: [`\Frontastic\Payment\AdyenBundle\Domain\AdyenService`](../../src/php/AdyenBundle/Domain/AdyenService.php)

## Methods

* [__construct()](#__construct)
* [fetchPaymentMethodsForCart()](#fetchpaymentmethodsforcart)
* [makePayment()](#makepayment)
* [submitPaymentDetails()](#submitpaymentdetails)

### __construct()

```php
public function __construct(
    \Adyen\Client $adyenClient,
    \Symfony\Component\Routing\Generator\UrlGeneratorInterface $router,
    \Frontastic\Common\CartApiBundle\Domain\CartApi $cartApi,
    string $clientKey,
    string $environment,
    array $originKeys,
    array $additionalPaymentConfig = []
): mixed
```

Argument|Type|Default|Description
--------|----|-------|-----------
`$adyenClient`|`\Adyen\Client`||
`$router`|`\Symfony\Component\Routing\Generator\UrlGeneratorInterface`||
`$cartApi`|`\Frontastic\Common\CartApiBundle\Domain\CartApi`||
`$clientKey`|`string`||
`$environment`|`string`||
`$originKeys`|`array`||
`$additionalPaymentConfig`|`array`|`[]`|

Return Value: `mixed`

### fetchPaymentMethodsForCart()

```php
public function fetchPaymentMethodsForCart(
    \Frontastic\Common\CartApiBundle\Domain\Cart $cart,
    \Frontastic\Common\ProductApiBundle\Domain\ProductApi\Locale $locale,
    string $origin
): AdyenPaymentMethodsResult
```

Argument|Type|Default|Description
--------|----|-------|-----------
`$cart`|`\Frontastic\Common\CartApiBundle\Domain\Cart`||
`$locale`|`\Frontastic\Common\ProductApiBundle\Domain\ProductApi\Locale`||
`$origin`|`string`||

Return Value: [`AdyenPaymentMethodsResult`](AdyenPaymentMethodsResult.md)

### makePayment()

```php
public function makePayment(
    \Frontastic\Common\CartApiBundle\Domain\Cart $cart,
    array $paymentMethod,
    ?array $browserInfo,
    \Frontastic\Common\ProductApiBundle\Domain\ProductApi\Locale $locale,
    string $origin,
    ?string $clientIp,
    ?string $shopperReference
): AdyenPaymentResult
```

Argument|Type|Default|Description
--------|----|-------|-----------
`$cart`|`\Frontastic\Common\CartApiBundle\Domain\Cart`||
`$paymentMethod`|`array`||
`$browserInfo`|`?array`||
`$locale`|`\Frontastic\Common\ProductApiBundle\Domain\ProductApi\Locale`||
`$origin`|`string`||
`$clientIp`|`?string`||
`$shopperReference`|`?string`||

Return Value: [`AdyenPaymentResult`](AdyenPaymentResult.md)

### submitPaymentDetails()

```php
public function submitPaymentDetails(
    \Frontastic\Common\CartApiBundle\Domain\Cart $cart,
    string $paymentId,
    array $details,
    string $paymentData,
    \Frontastic\Common\ProductApiBundle\Domain\ProductApi\Locale $locale
): AdyenPaymentResult
```

Argument|Type|Default|Description
--------|----|-------|-----------
`$cart`|`\Frontastic\Common\CartApiBundle\Domain\Cart`||
`$paymentId`|`string`||
`$details`|`array`||
`$paymentData`|`string`||
`$locale`|`\Frontastic\Common\ProductApiBundle\Domain\ProductApi\Locale`||

Return Value: [`AdyenPaymentResult`](AdyenPaymentResult.md)

Generated with [Frontastic API Docs](https://github.com/FrontasticGmbH/apidocs).
