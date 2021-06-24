#  AdyenServiceFactory

**Fully Qualified**: [`\Frontastic\Payment\AdyenBundle\Domain\AdyenServiceFactory`](../../src/php/AdyenBundle/Domain/AdyenServiceFactory.php)

## Methods

* [__construct()](#__construct)
* [factorForProject()](#factorforproject)

### __construct()

```php
public function __construct(
    \Symfony\Component\Routing\Generator\UrlGeneratorInterface $router,
    \Frontastic\Common\CartApiBundle\Domain\CartApi $cartApi
): mixed
```

Argument|Type|Default|Description
--------|----|-------|-----------
`$router`|`\Symfony\Component\Routing\Generator\UrlGeneratorInterface`||
`$cartApi`|`\Frontastic\Common\CartApiBundle\Domain\CartApi`||

Return Value: `mixed`

### factorForProject()

```php
public function factorForProject(
    \Frontastic\Common\ReplicatorBundle\Domain\Project $project
): AdyenService
```

Argument|Type|Default|Description
--------|----|-------|-----------
`$project`|`\Frontastic\Common\ReplicatorBundle\Domain\Project`||

Return Value: [`AdyenService`](AdyenService.md)

Generated with [Frontastic API Docs](https://github.com/FrontasticGmbH/apidocs).
