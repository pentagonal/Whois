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

namespace {

    use Composer\Autoload\ClassLoader;

    date_default_timezone_set('America/New_York');

    /**
     * @var ClassLoader $autoLoader
     */
    $autoLoader = require __DIR__ .'/../vendor/autoload.php';
    // add Loader
    $autoLoader->addPsr4('Pentagonal\\Tests\\PhpUnit\\', __DIR__ . '/PhpUnit/');
    $autoLoader->register();
}
