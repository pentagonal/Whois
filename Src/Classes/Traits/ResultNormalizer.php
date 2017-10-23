<?php
/**
 * This package contains some code that reused by other repository(es) for private uses.
 * But on some certain conditions, it will also allowed to used as commercials project.
 * Some code & coding standard also used from other repositories as inspiration ideas.
 * And also uses 3rd-Party as to be used as result value without their permission but permit to be used.
 *
 * @license GPL-3.0  {@link https://www.gnu.org/licenses/gpl-3.0.en.html}
 * @copyright (c) 2017. Pentagonal Development
 * @author pentagonal <org@pentagonal.org>
 */

namespace Pentagonal\WhoIs\Traits;

use Pentagonal\WhoIs\App\ArrayCollector;
use Pentagonal\WhoIs\App\Validator;
use Pentagonal\WhoIs\App\WhoIsRequest;
use Pentagonal\WhoIs\Util\DataParser;

/**
 * Trait ResultNormalizer
 * @package Pentagonal\WhoIs\Traits
 */
trait ResultNormalizer
{
    /**
     * Clean whois comment result
     * the comment string started with # and %
     *
     * @param string $data
     *
     * @return mixed|string
     */
    public function cleanComment(string $data) : string
    {
        return trim(preg_replace(
            '/^([ ]+)?(?:\#|\%)(?:[^\n]+)?\n?/m',
            '',
            str_replace("\r", "", $data)
        ));
    }

    /**
     * Clean string ini comment
     * dot ini comment is # and ;
     *
     * @param string $data
     *
     * @return string
     */
    public function cleanIniComment(string $data) : string
    {
        $data = trim($data);
        if ($data == '') {
            return $data;
        }

        return preg_replace(
            '/^(?:\;|\#)[^\n]+\n?/mu',
            '',
            $data
        );
    }

    /**
     * Clean Slashed Comment
     *
     * @param string $data
     *
     * @return string
     */
    public function cleanSlashComment(string $data) : string
    {
        $data = trim($data);
        if ($data == '') {
            return $data;
        }

        return preg_replace(
            '/^(?:\/\/)[^\n]+\n?/smu',
            '',
            $data
        );
    }

    /**
     * Clean Multiple whitespace
     *
     * @param string $data
     * @param bool $allowEmptyNewLine allow one new line
     *
     * @return string
     */
    public function normalizeWhiteSpace(string $data, $allowEmptyNewLine = false) : string
    {
        $data = str_replace(
            ["\r\n", "\t",],
            ["\n", " "],
            $data
        );

        if (!$allowEmptyNewLine) {
            return trim(preg_replace(['/^[\s]+/m', '~(\n)[ ]+~'], ['', '$1'], $data));
        }

        return trim(preg_replace(
            ['/(?!\n)([\s])+/m', '/(\n)[ ]+/', '/([\n][\n])[\n]+/m'],
            '$1',
            $data
        ));
    }

    /**
     * Clean WhoIs result informational data like, ICANN URL or comment whois
     * or ads or etc.
     *
     * @param string $data
     *
     * @return mixed|string
     */
    public function cleanInformationalData(string $data) : string
    {
        if (trim($data) === '') {
            return $data;
        }

        return rtrim(preg_replace(
            '~
            (?:
                \>\>\>?   # information
                | Terms\s+of\s+Use\s*:\s+Users?\s+accessing  # terms
                | URL\s+of\s+the\s+ICANN\s+WHOIS # informational from icann
                | NOTICE\s+AND\s+TERMS\s+OF\s+USE\s*: # dot ph comment
                | (\#\s*KOREAN\s*\(UTF8\)\s*)?상기\s*도메인이름은 
            ).*
            ~isx',
            '',
            preg_replace('/query[^\:]+[^\n]+/mi', '', $data)
        ));
    }

    /**
     * Normalize Whois Result
     *
     * @param string $data
     *
     * @return string
     */
    protected function normalizeWhoIsDomainResultData(string $data) : string
    {
        $data = str_replace("\r", "", $data);
        // sanitize for .BE domain
        if (strpos($data, ":\n\t")) {
            $arr = explode("\n", $data);
            $currentKey = null;
            foreach ($arr as $key => $value) {
                $arr[$key] = trim($value);
                if (trim($value) == '' || substr(trim($value), 0, 1) == '%') {
                    continue;
                }

                if (substr(rtrim($arr[$key]), -1) === ':'
                    && isset($arr[$key+1])
                    && substr($arr[$key+1], 0, 1) === "\t"
                ) {
                    unset($arr[$key]);
                    $currentKey = trim($value);
                    if (preg_match('/\t[a-z0-9\s]+\s*\:/i', $arr[$key+1])) {
                        $currentKey = rtrim($currentKey, ':');
                    }
                    continue;
                }

                if (substr($value, 0, 1) === "\t") {
                    $arr[$key] = "{$currentKey} {$arr[$key]}";
                }
            }
            $data = implode("\n", $arr);
            unset($arr);
        }

        // sanitize .kr domain
        if (preg_match('/Name\s*Server\s+Host\s*Name[^\:]+\:/mi', $data)) {
            $callBack = function ($match) {
                $prefix = stripos($match[0], 'Primary') !== false
                    ? 'Primary'
                    : 'Secondary';
                $match = $match[1];
                $match = preg_replace(
                    [
                        '/\s*IP\s*Address[^\n]+/smi',
                        '/\s*Host\s*Name\s*/smi',
                    ],
                    ['', "\n{$prefix} Name Server"],
                    $match
                );
                return trim($match);
            };
            $data = preg_replace_callback(
                [
                    '~
                      Primary\s+Name\s*Server[\n]+\s*
                        ((?:Host\s*Name|IP\s*Address)[^\:]+\:\s+(?:(?!\n\n)[\s\S])*)
                    ~xsmi',
                    '~
                      Secondary\s+Name\s*Server[\n]+\s*
                        ((?:Host\s*Name|IP\s*Address)[^\:]+\:\s+(?:(?!\n\n)[\s\S])*)      
                    ~xsmi',
                ],
                $callBack,
                $data
            );
        }
        // sanitize .jp domain
        if (stripos($data, '.jp') !== false && preg_match('~\[(Domain\s*)?Name\]|\[Name\s+Server\]~xsi', $data)) {
            $arrayData = [];
            // convert comment
            $data = preg_replace('/\[\s+([^\n]+)\]/m', '% $1', $data);
            if (stripos($data, '[Registrant]') !== false && strpos($data, '[Name]') !== false) {
                $data = str_ireplace("\n[Registrant]", "\n[Registrar] ", $data);
            }
            $arrayDataSplit = explode("\n\n", $data);
            foreach ($arrayDataSplit as $key => $v) {
                $v = preg_replace('/^(?:[a-z]+\.\s?)?\[([^\]]+)\]/m', '$1:', $v);
                if ($v && $v[0] != '%' && preg_match('~([a-z]+[^\n]+)(\n\s{3,}[^\n]+)+~smi', $v)) {
                    $v = preg_replace_callback(
                        '~(?P<name>^[a-z]+[^\:]+)(?P<line>\:[^\n]+)(?P<val>(?:\n\s{3,}[^\n]+)+)~smiu',
                        function ($match) {
                            $match = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);
                            $match['name'] = rtrim($match['name']);
                            $match['line'] = rtrim($match['line'], '( )');
                            $match['val']  = preg_replace(
                                '~(\s)+~',
                                '$1',
                                str_replace("\n", ' ', $match['val'])
                            );
                            $match['val'] = rtrim($match['val'], '( )');
                            return ($match['name'] . $match['line'] . $match['val']);
                        },
                        $v
                    );
                }
                if (stripos(trim($v), 'Domain Information') === 0
                    || ($isContact = stripos(trim($v), 'Contact Information') === 0)
                ) {
                    $v = ltrim(preg_replace('/^[^\n]+/', '', ltrim($v)));
                    $v = preg_replace('/\n\s+/', ' ', $v);
                    if (isset($isContact) && $isContact) {
                        // fix name
                        $v = preg_replace_callback(
                            '~^([^\:]+\:)([^\n]+)?~m',
                            function ($match) {
                                $registrant = 'Registrant';
                                // fix spaces
                                $length   = strlen($registrant) + 1;
                                $match[1] = "{$registrant} {$match[1]}";
                                $match[2] = preg_replace(
                                    "~^[\s]{1,{$length}}~",
                                    '',
                                    $match[2]
                                );
                                return $match[1] . $match[2];
                            },
                            $v
                        );
                        $matchCountry = false;
                        if (preg_match('~Country\:\s+([^\n]+)?~', $data, $match)) {
                            $matchCountry = $match[1];
                        }
                        $v = preg_replace('/Postal\s+(Address(?:[^\:]+)?\:)/i', '$1       ', $v);
                        // split city, state & address
                        $v = preg_replace_callback(
                            '/^Registrant\s+Address\:[^\n]+/m',
                            function ($match) use ($matchCountry) {
                                $match = rtrim($match[0]);
                                // get space
                                preg_match('~^Registrant\s+Address\:(\s*)~i', $match, $space);
                                $space = !empty($space[1]) ? $space[1] : '    ';
                                $explodeArrayAddress = array_map('trim', explode(',', $match));
                                $state  = array_pop($explodeArrayAddress);
                                $city   = array_pop($explodeArrayAddress);
                                $country = $matchCountry;
                                $street = implode(', ', $explodeArrayAddress);
                                if (!$country && preg_match('~(.+)\s+([0-9]+[0-9\-][0-9]{2,})$~', $city, $match)) {
                                    $country = $state;
                                    $state  = $match[1];
                                    if (substr_count($street, ' ') > 2) {
                                        $explodeArrayAddress = explode(' ', $street);
                                        $city = array_pop($explodeArrayAddress);
                                        $street = rtrim(implode(' ', $explodeArrayAddress));
                                    }
                                }
                                $content = "{$street}\n";
                                $content .= "Registrant City:   {$space}{$city}\n";
                                $content .= "Registrant State:  {$space}{$state}\n";
                                $content .= "Registrant Country:  {$space}{$country}\n";
                                return $content;
                            },
                            $v
                        );
                    }
                }

                $arrayData[] = $v;
            }

            $data = implode("\n\n", $arrayData);
            unset($arrayDataSplit);
        }

        if (stripos($data, 'Algorithm') === false || stripos($data, 'Digest') === false) {
            return $data;
        }

        // fix for DNSSSEC
        $placeHolder = "[__".microtime(true)."__]";
        $data = preg_replace_callback(
            '/
                (?P<name>
                  (?:
                    DS\s*Key(?:\s*Tag)?
                    | Algorithm
                    | Digest\s*Type
                    | Digest\s*
                  )
                )\s*(?P<selector>[0-9]+)(?:[^\:]+)?\:(?P<values>[0-9a-f]+)[^\n]*
            /mxi',
            function ($match) use ($placeHolder) {
                if (strpos($match['name'], 'DS') !== false) {
                    return "DNSSEC DS Data: {$match['values']}";
                }
                return $placeHolder.$match['values'];
            },
            $data
        );

        $data = str_replace(["\n{$placeHolder}", $placeHolder,], " ", $data);
        $data = $this->normalizeWhiteSpace($data);
        return $data;
    }

    /**
     * Sanitize for Request this for child class that maybe
     *
     * @param WhoIsRequest $request
     * @param Validator $validator
     *
     * @return WhoIsRequest
     */
    protected function normalizeAfterRequest(WhoIsRequest $request, Validator $validator) : WhoIsRequest
    {
        $domain = $request->getTargetName();
        if ($domain && $validator->isValidDomain($domain)) {
            $extension = $validator->splitDomainName($domain)->getBaseExtension();
        }

        if (empty($extension)) {
            return $request;
        }

        // with extensions logic
        switch ($extension) {
            case 'ph':
                if (stripos($request->getServer(), 'https://whois.dot.ph/?') !== 0) {
                    return $request;
                }
                $body = $request->getBodyString();
                if (trim($body) === '') {
                    return $request;
                }
                $parser = DataParser::htmlParenthesisParser('main', $body);
                if (count($parser) === 0) {
                    (stripos($body, '<html') !== false) && $request->setBodyString('');
                    return $request;
                }

                /**
                 * @var ArrayCollector $collector
                 */
                $collector = $parser->last();
                if (!is_string(($body = $collector->get('html')))) {
                    return $request;
                }

                $parser = DataParser::htmlParenthesisParser('pre', $body);
                if (count($parser) === 0) {
                    return $request;
                }
                $collector = $parser->last();
                if (!is_string(($body = $collector->get('html')))) {
                    return $request;
                }
                if (!empty($body)) {
                    $body = trim(preg_replace('~<br[^>]*>~i', "\n", $body));
                    $body = preg_replace('~^[ ]+~m', '', strip_tags($body));
                    $request->setBodyString(trim($body));
                }
                break;
            case 'vi':
                if (stripos(
                    $request->getServer(),
                    'https://secure.nic.vi/whois-lookup'
                ) !== 0 || trim(($body = $request->getBodyString())) === ''
                ) {
                    return $request;
                }

                $parser = DataParser::htmlParenthesisParser('pre', $body);
                // if not match
                if (count($parser) === 0) {
                    (stripos($body, '<html') !== false) && $request->setBodyString('');
                    return $request;
                }

                foreach ($parser as $key => $collector) {
                    // reset
                    $body = '';
                    if (!($selector = $collector->get('selector')) instanceof ArrayCollector
                        || ! is_array($class = $selector->get('class'))
                        || ! in_array('result-pre', array_map('strtolower', $class))
                        || ! is_string(($body = $collector->get('html')))
                    ) {
                        continue;
                    }
                    break;
                }

                if (!empty($body)) {
                    $body = trim(preg_replace('~<\/?(?:span|font|br)([^>]+)?>~i', "", $body));
                    $body = preg_replace('~^[^\n]+~', '', $body);
                    $request->setBodyString(trim($body));
                }
                break;
        }

        return $request;
    }
}
