
## Version 1.2.0 (2023-10-11)

** New Features and Improvements **

- Add compatibiltiy with Symfony 5.4.


## Version 1.1.0  (2022-03-07)

* feat: add paymentMethod to Adyen payments

## Version 1.0.12  (2022-03-07)

* fix(FP-1582): include missing fields for Adyen afterpay

## Version 1.0.11  (2021-07-07)

* fix: Tell symfony to ignore our swagger annotations

## Version 1.0.10  (2021-05-26)

* feat: Pass on state to adyen, if available

## Version 1.0.9  (2021-05-18)

* fix: Fix the parent construct on AdyenController to CartController

## Version 1.0.8  (2021-05-05)

chore: increased catwalk library version requested

## Version 1.0.7  (2021-05-05)

* chore(FP-91) Revert CartApiController is used by AdyenController too, refactored AdyenController

## Version 1.0.6  (2021-03-30)

* fix: Remove phpcpd from all projects
* fix: remove useless null check

## Version 1.0.5  (2021-03-19)

* feat: pass session id as query parameter to adyen redirect URL

## Version 1.0.4  (2021-03-05)

* fix: remove hardcoded test environment from adyen

## Version 1.0.3  (2021-03-05)

* feat: enable adyen payment integration to be usable for live environment

## Version 1.0.2  (2021-03-05)

* feat: enable adyen payment integration to be usable for live environment
* fix: don’t throw on missing additional config
* feat: allow additional payment config for adyen
* feat: update Adyen PHP SDK
* feat: include amount in payment configuration

## Version 1.0.1  (2021-02-24)

* feat: allow changing Adyen environment
* feat: include field for CC holder name for Adyen
* feat: include additional data in Adyen payment request
* fix: make browserInfo optional for Adyen
* fix: removed fixed territory on Adyen integration

## Version 1.0.0  (2021-02-10)

* feat: requested minimum version of catwalk library
* feat(fp-90): catwalk controllers (#580)
* chore: Update PHPUnit xsd versions and formatting
* fix: Remaining dependencies to work with common 2.0
* fix: Set composer platform to PHP 7.4
* fix: Increased Adyen PHP version requirements
* chore: Back to PHPUnit 7 – 8 does not run at all with 7.2
* chore: Updated payment/adyen to 7.4
* fix: don't use the JSON encoder for Adyen results
* feat: use clientKey for additional adyen details

## Version 1.0.0-beta.2  (2021-02-10)

* feat: requested minimum version of catwalk library
* !feat(fp-90) catwalk controllers (#580)
* chore: Update PHPUnit xsd versions and formatting
* fix: Remaining dependencies to work with common 2.0
* fix: Set composer platform to PHP 7.4
* fix: Increased Adyen PHP version requirements
* chore: Back to PHPUnit 7 – 8 does not run at all with 7.2
* chore: Updated payment/adyen to 7.4
* fix: don't use the JSON encoder for Adyen results
* feat: use clientKey for additional adyen details

## Version 1.0.0-beta.1  (2020-07-28)

* Initial release
