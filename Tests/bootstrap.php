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

$count = 0;
do {
    $count++;
    $file = __DIR__ .str_repeat('/..', $count).'/autoload.php';
    $break = file_exists($file);
    if ($break) {
        echo $count;
        require $file;
        break;
    }
} while(!$break && $count < 4);
