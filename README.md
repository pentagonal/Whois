# WHOIS CHECKER

[![Build Status](https://travis-ci.org/pentagonal/Whois.svg?branch=master)](https://travis-ci.org/pentagonal/Whois?branch=master)
[![Coverage Status](https://coveralls.io/repos/github/pentagonal/Whois/badge.svg?branch=master)](https://coveralls.io/github/pentagonal/Whois?branch=master)

**VERSION 2.0.0**


## Donate

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=KSR5SW7J22JXU)


**\#\# UNDER DEVELOPMENT \#\#**


## DESCRIPTION

This Package contains email validator, domain & network checker that `API READY`
Whois Result is returning Object base and Detail Result as array collection
also convert it into string is become `json pretty print` result.

## REQUIREMENTS

```txt
PHP 7.0 or later
cURL Php Extension

Suggest:

- ext-iconv 
- ext-mbstring
```
## EXAMPLE

**Example using Whois Checker**

```php
<?php
use Pentagonal\WhoIs\App\ArrayCacheCollector;
use Pentagonal\WhoIs\Interfaces\CacheInterface;
use Pentagonal\WhoIs\App\Checker;
use Pentagonal\WhoIs\App\Validator;

// use your own cache Class that must be implements CacheInterface
$cache = new ArrayCacheCollector();
// use extends object / create child class if you want use other validator
$validator = new Validator();
$options = [
    // this is for guzzle config
    'debug' => true,
    // for Checker Cache expire
    'cacheExpire' => 3600, 
];
/**
 * Create new instance object Checker
 * or just with empty params with 
 * $checker = new Checker()
 *
 * @param Validator $validator is nullable
 * @param CacheInterface $cache is nullable
 * eg : 
 * $checker = new Checker(null, null, ['options'=> 'value'])
 */
$checker = new Checker($validator, $cache, $options);

/**
 * Or just Create from static
 * or empty param with @uses Checker::createInstance()
 */
$checker = Checker::createInstance($validator, $cache, $options);

/* ------------------------------------------
 * Get From Domain Name
 * ---------------------------------------- */

$result = $checker->getFromDomain('domain.com', true);
// just echo or encode json to returning JSON Data
echo $result;
echo json_encode($result, JSON_PRETTY_PRINT);
// or get array
print_r($result->toArray());
/**
 * or get collector data detail
 * @var \Pentagonal\WhoIs\Abstracts\WhoIsResultAbstract
 */
print_r($result->getResultData());

/* ------------------------------------------
 * Get From Auto Detect Type
 * ---------------------------------------- */
$target = '8.8.8.8';
$result = $checker->getResult($target);

/* ------------------------------------------
 * Get From Multi Result
 * Will be follow given whois server
 * from result,
 * if value count more than 1 it was follow whois
 * server
 * ---------------------------------------- */
$result = $checker->getFollowServerResult($target);
```

**Check Multiple Data**

The checker support `multi_curl_init` to used for async request

```php
<?php
use Pentagonal\WhoIs\App\Checker;
use Pentagonal\WhoIs\App\WhoIsMultiResult;
use Pentagonal\WhoIs\App\WhoIsResult;

$checker = Checker::createInstance();
$dataToCheck = [
    'google.com' => 'whois.google.com',
    '192.168.0.1',
    'AS123',
    'example.com'
];

/**
 * Requesting single result per data 
 * @var \Throwable[]|WhoIsResult[]|WhoIsMultiResult
 *
   WhoIsMultiResult Object (
       [storage:ArrayObject:private] => array(
         [google.com] => WhoIsResult object|\Throwable,
         // of (192.168.0.1)
         [0] => WhoIsResult object|\Throwable,
         // of (AS123)
         [1] => WhoIsResult object|\Throwable,
         // of (example.com)
         [2] => WhoIsResult object|\Throwable,
      )
  )
 *
 */
$result = $checker->getFromMultiple($dataToCheck);

/**
 * Requesting follow server per data 
 * @var WhoIsMultiResult|WhoIsMultiResult[]
 *
   WhoIsMultiResult Object (
       [storage:ArrayObject:private] => array(
         [google.com] => WhoIsMultiResult Object ( 
            // of (google.com)
            [storage:ArrayObject:private] => array(
                WhoIsResult object|\Throwable, ....
            )
         )
         [0] => WhoIsMultiResult Object (
            // of (192.168.0.1)
            [storage:ArrayObject:private] => array(
                WhoIsResult object|\Throwable, ....
            )
         ),
         [1] => WhoIsMultiResult Object (
            // of (AS123)
            [storage:ArrayObject:private] => array(
                WhoIsResult object|\Throwable, ....
            )
         )
         [2] => WhoIsMultiResult Object (
            // of (example.com)
            [storage:ArrayObject:private] => array(
                WhoIsResult object|\Throwable, ....
            )
         )
      )
  )
 *
 */
$result = $checker->getFromMultipleFollowResultServer($dataToCheck);

```
### INSTANTIATING & PROXY

Whois checker support connection behind the proxy.

except :`whois.arin.net` if script detect `whois.arin.net` as server, proxy will be automatically disabled.

***Instantiate Using Proxy***

```php
<?php
use Pentagonal\WhoIs\App\Checker;

/**
 * Proxy can be contains as a string (host:port)
 * or as array with:
 * [
 *  'host' => 'proxy.host',
 *  'port' => 'integer proxy port'
 * ]  
 */
$proxyChecker = Checker::createProxy('proxyhost:proxyport');
// disable proxy clone
$proxyChecker = $proxyChecker->withoutProxy();

/**
 * Or just create instant checker 
 */
$checker = Checker::createInstance();
// with proxy clone
$proxyChecker = $checker->withProxy('proxyhost:proxyport');

/**
 * checker still use wihout proxy,
 * because cloning the object Checker
 */
(($checker === $proxyChecker) === false);

```


## NOTE

```
Some extension does not provide whois server,
because some of registrar or TLD provider 
does not permit their whois data for public.
```

You can get `Whois Servers` list on : [Src/Data/Servers/AvailableServers.php](Src/Data/Extensions/AvailableServers.php)


##

**PLEASE DO NOT USE FOR PRODUCTION**

*Until the stable version has been release.

***All of methods or data will be change without prior notice.***

##

## LICENSE

`GPL-3.0` see [LICENSE](LICENSE)
