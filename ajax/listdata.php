<?php

header("Content-type:application/json");

include_once __DIR__ . "/../config.php";

if (isPHPDebug())
{
    ini_set('display_errors', 'On');
    error_reporting(E_ALL);
}

include_once __DIR__ . "/../lib/sysinfo.php";
include_once __DIR__ . "/../lib/logwrapper.php";

$entries = listEntries();

echo json_encode($entries);


