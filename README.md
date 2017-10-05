# Php WhoIs

Php Domain / ASN / IP WhoIs Checker

## REQUIREMENT

- php 5.6 or later (>= 7 is recommended)
- Extension internationalize enabled (`php-intl`)
- Php Socket enable (`fopen` & `fsockopen`)
- Allow to connect / port `43` for out-bond connection

## Usage

    # please contribute or just read the code
    # or just use IDE (eg: jetbrains phpstorm) to get auto complete

```php
<?php
/**
 * WhoIs detail returning server as key
 * returning @uses \ArrayObject 
 */
use Pentagonal\WhoIs\WhoIs;
use Pentagonal\WhoIs\Util\DataGetter;

$who = new WhoIs(new DataGetter());
/**
 * get data from whois with fully detail per registrant data
 * second param is follow whois or check if domain have internal server from registrant
 */
$who->getWhoIsWithArrayDetail('example.com', true); # instance of ArrayObject
/**
 * get data from whois with include alternative if there was alternative will be returning 2 array data
 * second param is clean unwanted string result domain if possible
 * third param is follow whois or check if domain have internal server from registrant
 */
$whois = $who->getWhoIs('example.com', true, true); # instance of ArrayObject
// to get end of result if possible detailed result whois data
$whois->last(); # string

/**
 * Get server address for whois from domain
 */
$who->getWhoIsServer('example.com'); # string
/**
 * get ip data result
 */
$who->getIpData('127.0.0.1'); # instance of ArrayObject

/**
 * get domain is registered or not
 * if returning null result is unknown otherwise boolean
 * true if registered or false is unregistered
 */
$who->isDomainRegistered('example.com');
// ... do your
```

## USED FOR

- Email Validator By Given Extensions
- Domain Validator
- Auto get of extensions 

## LICENSE

GPL-3.0 see [LICENSE](LICENSE)

## LINK

List Public Suffix : [https://publicsuffix.org/list/effective_tld_names.dat](https://publicsuffix.org/list/effective_tld_names.dat)

IANA List TLDS : [https://data.iana.org/TLD/tlds-alpha-by-domain.txt](https://data.iana.org/TLD/tlds-alpha-by-domain.txt)

## RESPONSIBLE

```

I'm not guaranteed that the script run perfectly & works like a charms.
But I want to help to build some useful code to use  & help other peoples.

If there was crash or other damage 
    (maybe got banned from ISP / The party of connect with whois & data)
All of risks being your responsibilities.

```

## USING COMPOSER TO INSTALL

```json
{
 // ... other
  "require": {
    // ... other repo
    "pentagonal/whois": "dev-master"
  }
}
```
or

```bash
composer require pentagonal/whois
```
