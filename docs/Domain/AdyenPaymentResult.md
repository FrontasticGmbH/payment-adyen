#  AdyenPaymentResult

**Fully Qualified**: [`\Frontastic\Payment\AdyenBundle\Domain\AdyenPaymentResult`](../../src/php/AdyenBundle/Domain/AdyenPaymentResult.php)

**Extends**: [`\Kore\DataObject\DataObject`](https://github.com/kore/DataObject)

Property|Type|Default|Required|Description
--------|----|-------|--------|-----------
`paymentId` | `string` |  | - | 
`resultCode` | `?string` |  | - | 
`merchantReference` | `?string` |  | - | 
`pspReference` | `?string` |  | - | 
`refusalReason` | `?string` |  | - | 
`action` | ?[`AdyenAction`](AdyenAction.md) |  | - | 
`details` | [`AdyenPaymentDetail`](AdyenPaymentDetail.md)[] | `[]` | - | 

## Methods

* [getDetailKeys()](#getdetailkeys)
* [__set()](#__set)

### getDetailKeys()

```php
public function getDetailKeys(): array
```

Return Value: `array`

### __set()

```php
public function __set(
    mixed $name,
    mixed $value
): void
```

Argument|Type|Default|Description
--------|----|-------|-----------
`$name`|`mixed`||
`$value`|`mixed`||

Return Value: `void`

Generated with [Frontastic API Docs](https://github.com/FrontasticGmbH/apidocs).
