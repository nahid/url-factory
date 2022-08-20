# UrlFactory

UrlFactory is a PHP package for working with URLs. Its offer you to best developer experience to manage and manipulate URLs. UrlFactory offers simple, yet powerful ways of working with query string, paths and domain.



## Requirements

- php >= 8.0

- ext-json

- ext-intl



## Installation

UrlFactory is available on [Packagist]([nahid/url-factory - Packagist](https://packagist.org/packages/nahid/url-factory)). You can directly install it in any package by running this command bellow



```bash
composer require nahid/url-factory
```

## Configuration

There are no extra configuration needed to run this package, but by passing config you can take more control over this package.



### Default Config

```php
// use Nahid\UrlFactory\Enum;
[
    Enum::CONFIG_KEY => 'secret-key',
    Enum::CONFIG_SIGNER => MD5UrlSigner::class,
    Enum::CONFIG_PSR_CACHE_INTERFACE => null,
    Enum::CONFIG_PSR_CLIENT_INTERFACE => null,
    Enum::CONFIG_PSR_REQUEST_FACTORY_INTERFACE => null,
]
```

> you can modify it as you want and pass into UrlFactory when initialize



## Syntax

```php
new Url([string $url, [array $config]]);
```

> Both arguments of the construct are optional



## Initialization

```php
use Nahid\UrlFactory\Url;
use Nahid\UrlFactory\Enum;


$url = new Url('http://app.staging.google.com.bd/private/search?q=bangladesh&page=1&limit=20', [
    Enum::CONFIG_KEY => 'random-secret-key',
]);
```

> We use `http://app.staging.google.com.bd/private/search?q=bangladesh&page=1&limit=20` for our next examples



## Example

#### URL Extract and Fetch

```php
use Nahid\UrlFactory\Url;
use Nahid\UrlFactory\Enum;


$url = new Url('http://app.staging.google.com.bd/private/search?q=bangladesh&page=1&limit=20', [
    Enum::CONFIG_KEY => 'random-secret-key',
]);

echo $url->getScheme() . "\n";
echo $url->getQuery() . "\n";
var_dump($url->getQueryParam()) . "\n";
echo $url->getPath() . "\n";
echo $url->domain()->getSubdomain() . "\n";
echo $url->domain()->getSuffix() . "\n";
echo $url->domain()->getTld() . "\n";

```



##### Output

```bash
http
q=bangladesh&page=1&limit=20
['q' => 'bangladesh', 'page '=> 1, 'limit' => 20]
private/search
app.staging
com.bd
com
```



### URL Modification

```php
use Nahid\UrlFactory\Url;
use Nahid\UrlFactory\Enum;


$url = new Url('http://app.staging.google.com.bd/private/search?q=bangladesh&page=1&limit=20', [
    Enum::CONFIG_KEY => 'random-secret-key',
]);


$url->useSchemeHttps()
    ->usePath('query')
    ->useQueryParams(['search'=> 'dhaka'])
    ->useFragment('top')
    ->domain(function(\Nahid\UrlFactory\Domain $domain) {
        $domain->useBaseName('bing')
            ->useSuffix('co.in')
            ->useSubdomain('app');
    });

echo $url->get()
```

#### Output

```bash
https://app.bing.co.in/query?search=dhaka#top
```



### Signed URL

```php
use Nahid\UrlFactory\Url;
use Nahid\UrlFactory\Enum;


$url = new Url('http://app.staging.google.com.bd/private/search?q=bangladesh&page=1&limit=20', [
    Enum::CONFIG_KEY => 'random-secret-key',
]);


$url->useSchemeHttps()
    ->usePath('query')
    ->useQueryParams(['search'=> 'dhaka'])
    ->useFragment('top')
    ->domain(function(\Nahid\UrlFactory\Domain $domain) {
        $domain->useBaseName('bing')
            ->useSuffix('co.in')
            ->useSubdomain('app');
    });

echo $url->sign(7)->get(); // Generate signed URL with 7 days validity
 
```



#### Output

```bash
https://app.bing.co.in/query?search=dhaka&expires=1661613403&signature=5fd3f94c145731a085f6964cb3c4d03d#top
```



## API List

> There are lots of API are available with this package. We'll update it ASAP