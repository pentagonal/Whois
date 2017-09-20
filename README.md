# Php WhoIs

Php Domain / ASN / IP WhoIs Checker

## REQUIREMENT

- php 5.6 or later
- Extension internationalize enabled
- Php Socket enable (`fopen` & `fsockopen`)
- Allow to connect / port `43` for outbond connection

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
// get data from whois with fully detail per registrant data
$who->getWhoIsWithArrayDetail('example.com'); # instance of ArrayObject
// get data from whois with include alternative if there was alternative will be returning 2 array data
$who->getWhoIs('example.com'); # instance of ArrayObject
// get server address for whois from domain
$who->getWhoIsServer('example.com'); # instance of ArrayObject
// get ip data result
$who->getIpData('127.0.0.1'); # instance of ArrayObject
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
