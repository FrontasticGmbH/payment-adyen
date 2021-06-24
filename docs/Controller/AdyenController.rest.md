# 

## `GET` `/paymentsMethod`

*Get available payment methods*

### Request Body

* null

### Responses

Status: 200

* `AdyenPaymentMethodsResult`

  * `paymentMethods`: collection of `AdyenPaymentMethod`

    * `type`: `string`

    * `name`: `string`

  * `configuration`: `array`

## `POST` `/payment`

*Make payment*

### Request Body

* `object` with:

  * `paymentMethod` as `AdyenPaymentMethod`

    * `type`: `string`

    * `name`: `string`

  * `browserInfo` as *optional* `object`

### Responses

Status: 200

* `AdyenPaymentResult`

  * `paymentId`: `string`

  * `resultCode`: *optional* `string`

  * `merchantReference`: *optional* `string`

  * `pspReference`: *optional* `string`

  * `refusalReason`: *optional* `string`

  * `action`: *optional* `AdyenAction`

    * `type`: `string`

    * `paymentData`: either of:

      * `string`

      * `null`

  * `details`: collection of `AdyenPaymentDetail`

    * `key`: `string`

Generated with [Frontastic API Docs](https://github.com/FrontasticGmbH/apidocs).
