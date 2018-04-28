<?php

function getIP()
{
	if (isset($_SERVER['SERVER_ADDR'])) return $_SERVER['SERVER_ADDR'];
	if (isset($_SERVER['HTTP_HOST'])) return $_SERVER['HTTP_HOST'];
	return "N/A";
}

function getOperatingSystem()
{
	if (!($os = shell_exec('/usr/bin/lsb_release -ds | cut -d= -f2 | tr -d \'"\''))) {
		if (!($os = shell_exec('cat /etc/system-release | cut -d= -f2 | tr -d \'"\''))) {
			if (!($os = shell_exec('cat /etc/os-release | grep PRETTY_NAME | tail -n 1 | cut -d= -f2 | tr -d \'"\''))) {
				if (!($os = shell_exec('find /etc/*-release -type f -exec cat {} \; | grep PRETTY_NAME | tail -n 1 | cut -d= -f2 | tr -d \'"\''))) {
					$os = 'N/A';
				}
			}
		}
	}
	$os = trim($os, '"');
	$os = str_replace("\n", '', $os);

	return $os;
}

function getHumanTime($seconds)
{
	$units = array(
		'year'   => 365*86400,
		'day'    => 86400,
		'h'   => 3600,
		'min' => 60,
		// 'second' => 1,
	);

	$parts = array();

	foreach ($units as $name => $divisor)
	{
		$div = floor($seconds / $divisor);

		if ($div == 0)
			continue;
		else
			if ($div == 1)
				$parts[] = $div.' '.$name;
			else
				$parts[] = $div.' '.$name.'s';
		$seconds %= $divisor;
	}

	$last = array_pop($parts);

	if (empty($parts))
		return $last;
	else
		return join(', ', $parts).', '.$last;
}

function getKernel()
{
	if ($kernel = shell_exec('/bin/uname -r')) return $kernel;

	return 'N/A';
}

function getUptime()
{
	if (($totalSeconds = shell_exec('/usr/bin/cut -d. -f1 /proc/uptime'))) return getHumanTime($totalSeconds);

	return "N/A";
}

function getBootupTime()
{
	if (($upt_tmp = shell_exec('cat /proc/uptime'))) return date('Y-m-d H:i:s', time() - intval(explode(' ', $upt_tmp)[0]));

	return 'N/A';
}

function fmtTime($ts)
{
	if ($ts == 0) return '';

	return gmdate("Y-m-d H:i:s", $ts);
}

function fmtSize($filesize, $precision = 2)
{
	$units = array('', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y');

	$lidu = 'XXX';
	foreach ($units as $idUnit => $unit)
	{
		$lidu = $unit;

		if ($filesize > 1024)
			$filesize /= 1024;
		else
			break;
	}

	return round($filesize, $precision).' '.$lidu.'B';
}

function getDiskData()
{
	if (!(exec('/bin/df -T | awk -v c=`/bin/df -T | grep -bo "Type" | awk -F: \'{print $2}\'` \'{print substr($0,c);}\' | tail -n +2 | awk \'{print $1","$2","$3","$4","$5","$6","$7}\'', $df))) {
		return 'N/A';
	} else {
		$mounted_points = array();
		$key = 0;

		$sumtotal = 0;
		$sumused = 0;
		$sumfree = 0;

		foreach ($df as $mounted) {
			list($filesystem, $type, $total, $used, $free, $percent, $mount) = explode(',', $mounted);

			if (strpos($type, 'tmpfs') !== false)
				continue;

			if (!in_array($mount, $mounted_points)) {
				$mounted_points[] = trim($mount);

				$sumtotal += $total;
				$sumused += $used;
				$sumfree += $free;
			}

			$key++;
		}
	}

	return fmtSize($sumfree * 1024) . ' / ' . fmtSize($sumtotal * 1024) . ' (' . round(100 * $sumfree/$sumtotal, 2) . '%)';
}