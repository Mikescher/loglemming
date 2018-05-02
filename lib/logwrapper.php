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

function cmp_mtime($a, $b)
{
	return $b["mtime"] - $a["mtime"];
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
	return processentries($json['entries'], '', $i);
}

function readLogFile($path)
{
	if (isSLLDebug())
	{
		sleep(2);
		return file_get_contents(getSLLDebugFile());
	}

	return shell_exec('sudo simpleloglist read ' . escapeshellarg($path));
}

function processentries($entries, $dirpath, &$i)
{
	$result = [];

	foreach ($entries as $e)
	{
		$e['path'] = ($dirpath==='') ? $e['name'] : ($dirpath . '/' . $e['name']);

		if ($e['type'] == 'file' || $e['type'] == 'compressed_file')
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
					'gzip'  => ($e['type'] == 'compressed_file'),
					'id'    => $i++,
					'path'  => ($dirpath==='') ? $canonical : ($dirpath . '/' . $canonical)
				];
				$result[$canonical] = $newentry;
			}
		}
		elseif ($e['type'] == 'dir' || $e['type'] == 'compressed_dir')
		{
			$result []=
			[
				'name'    => $e['name'],
				'type'    => $e['type'],
				'gzip'    => ($e['type'] == 'compressed_dir'),
				'id'      => $i++,
				'entries' => processentries($e['entries'], $e['path'], $i),
			];
		}

	}

	$resultentries = [];

	foreach ($result as &$e)
	{
		if ($e['type'] == 'file' || $e['type'] == 'compressed_file')
		{
			usort($e['files'], 'cmp_mtime');
			$sz = 0; foreach($e['files'] as $sze) $sz += $sze['size'];
			$e['size']      = $sz;
			$e['ctime']     = $e['files'][0]['ctime'];
			$e['mtime']     = $e['files'][0]['mtime'];
			$e['count']     = sizeof($e['files']);
			$e['fmt_mtime'] = fmtTime($e['mtime']);
			$e['fmt_ctime'] = fmtTime($e['ctime']);
			$e['fmt_size']  = fmtSize($e['size']);
			$e['fmt_count'] = "" . $e['count'];
		} 
		else if ($e['type'] == 'dir' || $e['type'] == 'compressed_dir')
		{
			$sz = 0; foreach($e['entries'] as $sze) $sz += $sze['size'];
			$e['size']      = $sz;
			$e['ctime']     = sizeof($e['entries'] == 0) ? 0 : $e['entries'][0]['ctime'];
			$e['mtime']     = sizeof($e['entries'] == 0) ? 0 : $e['entries'][0]['mtime'];
			$e['count']     = 0;
			$e['fmt_mtime'] = fmtTime($e['mtime']);
			$e['fmt_ctime'] = fmtTime($e['ctime']);
			$e['fmt_size']  = fmtSize($e['size']);
			$e['fmt_count'] = "";
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