<?php

include "../lib/sysinfo.php";
include "../lib/logwrapper.php";
include "../lib/util.php";

echo readLogFile($_GET['path']);