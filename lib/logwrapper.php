<?php

include_once __DIR__ . "/../config.php";

function startsWith($haystack, $needle)
{
	$length = strlen($needle);
	return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle)
{
	$length = strlen($needle);

	return $length === 0 || (substr($haystack, -$length) === $needle);
}

function entry_cmp($a, $b)
{
	return strcmp(strtolower($a["name"]), strtolower($b["name"]));
}

function cmp_mdate($a, $b)
{
	return $b["mdate"] - $a["mdate"];
}

function listEntries()
{
	if (isSLLDebug())
	{
		$json = json_decode(file_get_contents('F:\temp\simpleloglist_list.txt'), true);
	}
	else
	{
		$json = json_decode(shell_exec('sudo simpleloglist list'), true);
	}

	$i = 1000;
	return processentries($json['entries'], $i);
}

function readLogFile($path)
{
	if (isSLLDebug())
	{
		sleep(2);
		return file_get_contents('F:\Stash\aleph_test\A elementum molestie aenean litora primis.txt');
	}

	return shell_exec('sudo simpleloglist read ' . escapeshellarg($path));
}

function processentries($entries, &$i)
{
	$result = [];

	foreach ($entries as $e)
	{
		if ($e['type'] == 'file')
		{
			$canonical = get_canonical_entryname($e);

			if (key_exists($canonical, $result))
			{
				$result[$canonical]['files'] []= $e;
			}
			else
			{
				$newentry =
				[
					'name'  => $canonical,
					'type'  => 'file',
					'files' => [ $e ],
					'gzip'  => endsWith($e['name'], '.gz'),
					'id'    => $i++,
				];
				$resultentries []= $newentry;
				$result[$canonical] = $newentry;
			}
		}
		elseif ($e['type'] == 'dir')
		{
			$result []=
			[
				'name'    => $e['name'],
				'type'    => $e['type'],
				'id'      => $i++,
				'entries' => processentries($e['entries'], $i),
			];
		}

	}

	$resultentries = [];

	foreach ($result as &$e)
	{
		if ($e['type'] == 'file')
		{
			usort($e['files'], 'cmp_mdate');
			$e['size'] = $e['files'][0]['size'];
			$e['cdate'] = $e['files'][0]['cdate'];
			$e['mdate'] = $e['files'][0]['mdate'];
		}

		$resultentries []= $e;
	}

	usort($resultentries, "entry_cmp");

	return $resultentries;
}

function get_canonical_entryname($entry)
{
	$n = $entry['name'];

	for(;;)
	{
		if (endsWith($n, '.log'))
		{
			$n = substr($n, 0, strlen($n)-strlen('.log'));
			continue;
		}

		if (endsWith($n, '.gz'))
		{
			$n = substr($n, 0, strlen($n)-strlen('.gz'));
			continue;
		}

		if (endsWith($n, '.tar'))
		{
			$n = substr($n, 0, strlen($n)-strlen('.tar'));
			continue;
		}

		$matches = [];
		if (preg_match('/.*(\.[0-9]+)$/', $n, $matches))
		{
			$n = substr($n, 0, strlen($n)-strlen($matches[1]));
			continue;
		}

		$matches = [];
		if (preg_match('/.*([\._][12][0-9]{3}-[01][0-9]-[0-3][0-9])$/', $n, $matches))
		{
			$n = substr($n, 0, strlen($n)-strlen($matches[1]));
			continue;
		}

		return $n;
	}

	return '?';
}