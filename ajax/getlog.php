<?php

include "../lib/sysinfo.php";
include "../lib/logwrapper.php";

echo readLogFile($_GET['path']);