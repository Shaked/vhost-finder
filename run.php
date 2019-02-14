<?php

require 'vendor/autoload.php';

use Symfony\Component\Console\Application;

/**
 * @param $delimiter
 * @param $string
 * @param $trim
 * @return mixed
 */
function explodeNoEmpty($delimiter, $string, $trim = false) {
    $arr = array_map('trim', explode($delimiter, $string));

    return array_filter($arr, function ($a) {
        return $a != '';
    });

}

$application = new Application();

$application->add(new \App\VHostFinderCommand());

// ... register commands

$application->run();