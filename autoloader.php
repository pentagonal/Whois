<?php
return spl_autoload_register(function ($className) {
    static $classMapLower;
    if (!isset($classMapLower)) {
        $classMapLower = array_change_key_case(require __DIR__ . '/classMap.php', CASE_LOWER);
    }

    $nameSpace = 'Pentagonal\\WhoIs\\';
    $className = ltrim($className, '\\');
    // check match
    if (stripos($className, $nameSpace) !== 0) {
        return;
    }
    $classNameLower = strtolower($className);
    if (isset($classMapLower[$classNameLower])) {
        /** @noinspection PhpIncludeInspection */
        require $classMapLower[$classNameLower];
    }
});
