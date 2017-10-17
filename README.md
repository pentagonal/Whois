# WHOIS CHECKER

[![Build Status](https://travis-ci.org/pentagonal/Whois.svg?branch=dev)](https://travis-ci.org/pentagonal/Whois)
[![Coverage Status](https://coveralls.io/repos/github/pentagonal/Whois/badge.svg?branch=dev)](https://coveralls.io/github/pentagonal/Whois?branch=dev)

**VERSION 2.0.0**


## Donate

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=KSR5SW7J22JXU)


**\#\# UNDER DEVELOPMENT \#\#**


## DESCRIPTION

This Package contains email validator, domain & network checker that `API READY`
Whois Result is returning Object base and Detail Result as array collection
also convert it into string is become `json pretty print` result.

## EXAMPLE

**Example using Whois Checker**

```php
<?php
use Pentagonal\WhoIs\App\ArrayCollector;
use Pentagonal\WhoIs\App\ArrayCacheCollector;
use Pentagonal\WhoIs\Interfaces\CacheInterface;
use Pentagonal\WhoIs\App\Checker;
use Pentagonal\WhoIs\App\Validator;
use Pentagonal\WhoIs\App\WhoIsResult;

/**
 * @var CacheInterface $cache
 * @var Validator      $validator
 */
// use your own cache Class that must be implements CacheInterface
$cache = new ArrayCacheCollector();
// use extends object / create child class if you want use other validator
$validator = new Validator();
$checker = new Checker($validator, $cache);

/**
 * @var ArrayCollector $collector
 */
$collector = $checker->getFromDomain('domain.com', true);
if (count($collector) > 0) {
    /**
     * @var WhoIsResult $lastData
     */
    // use last data to get last result / follow whois
    $lastData = $collector->last();
    // just echo or encode json to returning JSON Data
    echo $lastData;
    echo json_encode($lastData, JSON_PRETTY_PRINT);
    // or get array
    print_r($lastData->toArray());
    // or get collector data detail
    print_r($lastData->getDataDetail());
}

```

## NOTE

```
Some extension does not provide whois server,
because some of registrar or TLD provider 
does not permit their whois data for public.
```

You can get `Whois Servers` list on : [Src/Data/Servers/AvailableServers.php](Src/Data/Servers/AvailableServers.php)



## LICENSE

`GPL-3.0` see [LICENSE](LICENSE)

