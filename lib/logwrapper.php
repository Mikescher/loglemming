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

function assoc_max($arr, $key, $def)
{
	if (count($arr) === 0) return $def;
	$v = $arr[0][$key];
	foreach ($arr as $av) $v = max($v, $av[$key]);
	return $v;
}

function assoc_min($arr, $key, $def)
{
	if (count($arr) === 0) return $def;
	$v = $arr[0][$key];
	foreach ($arr as $av) $v = min($v, $av[$key]);
	return $v;
}

function entry_cmp($a, $b)
{
	$ia = strval(array_search($a["type"], [ 'dir', 'file', 'compressed_file', 'symlink', 'compressed_dir', 'combined_pack' ]));
	$ib = strval(array_search($b["type"], [ 'dir', 'file', 'compressed_file', 'symlink', 'compressed_dir', 'combined_pack' ]));

	if ($ia === '0' || $ia === '1' || $ia === '2') $ia = '1';
	if ($ib === '0' || $ib === '1' || $ib === '2') $ib = '1';

	if ($ia === '4' || $ia === '5') $ia = '4';
	if ($ib === '4' || $ib === '5') $ib = '4';

	$r = strcmp($ia, $ib);
	if ($r !== 0)
	{
		return $r;
	}

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
		$json = json_decode(file_get_contents(getSLLDebugFileList()), true);
	}
	else
	{
		$json = json_decode(shell_exec('sudo simpleloglist list'), true);
	}

	$i = 1000;
	return processentries($json['entries'], '', 'root', $i);
}

function readLogFile($path)
{
	if (isSLLDebug())
	{
		sleep(2);
		return file_get_contents(getSLLDebugFileRead());
	}

	return shell_exec('sudo simpleloglist read ' . escapeshellarg($path));
}

function processentries($entries, $dirpath, $owner_type, &$i)
{
	$result = [];

	// [1] find (+combine) log rotation && pack files

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
		elseif ($e['type'] == 'dir')
		{
			$result []=
				[
					'name'    => $e['name'],
					'type'    => $e['type'],
					'gzip'    => ($e['type'] == 'compressed_dir'),
					'id'      => $i++,
					'entries' => processentries($e['entries'], $e['path'], $e['type'], $i),
				];
		}
		elseif ($e['type'] == 'compressed_dir')
		{
			$packname = get_pack_zipdirname($e);

			if ($packname == null)
			{
				$result []=
				[
					'name'    => $e['name'],
					'type'    => $e['type'],
					'gzip'    => ($e['type'] == 'compressed_dir'),
					'id'      => $i++,
					'entries' => processentries($e['entries'], $e['path'], $e['type'], $i),
				];
			}
			else
			{
				if (key_exists('pack::' . $packname, $result))
				{
					$subentries = processentries($e['entries'], $e['path'], $e['type'], $i);
					$sz = 0; foreach($subentries as $sze) $sz += $sze['size'];

					$result['pack::' . $packname]['entries'] []=
						[
							'name'    => $e['name'],
							'type'    => $e['type'],
							'gzip'    => ($e['type'] == 'compressed_dir'),
							'id'      => $i++,

							'entries' => $subentries,

							'size'      => $sz,
							'ctime'     => assoc_max($subentries, 'ctime', 0),
							'mtime'     => assoc_max($subentries, 'mtime', 0),
							'count'     => count($subentries),
							'fmt_ctime' => fmtTime(assoc_max($subentries, 'ctime', 0)),
							'fmt_mtime' => fmtTime(assoc_max($subentries, 'mtime', 0)),
							'fmt_size'  => fmtSize($sz),
							'fmt_count' => "",
						];
				}
				else
				{
					$subentries = processentries($e['entries'], $e['path'], $e['type'], $i);
					$sz = 0; foreach($subentries as $sze) $sz += $sze['size'];

					$newentry =
					[
						'name'    => $packname,
						'type'    => 'combined_pack',
						'gzip'    => false,
						'id'      => $i++,
						'entries' =>
						[
							[
								'name'    => $e['name'],
								'type'    => $e['type'],
								'gzip'    => ($e['type'] == 'compressed_dir'),
								'id'      => $i++,

								'entries' => $subentries,

								'size'      => $sz,
								'ctime'     => assoc_max($subentries, 'ctime', 0),
								'mtime'     => assoc_max($subentries, 'mtime', 0),
								'count'     => 1,
								'fmt_mtime' => fmtTime(assoc_max($subentries, 'ctime', 0)),
								'fmt_ctime' => fmtTime(assoc_max($subentries, 'mtime', 0)),
								'fmt_size'  => fmtSize($sz),
								'fmt_count' => "",
							]
						],
					];
					$result['pack::' . $packname] = $newentry;
				}
			}

		}
	}

	// [2] combine multi-items (log rotation + compressed_dir)

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
			$e['ctime']     = assoc_max($e['entries'], 'ctime', 0);
			$e['mtime']     = assoc_max($e['entries'], 'mtime', 0);
			$e['count']     = 0;
			$e['fmt_mtime'] = fmtTime($e['mtime']);
			$e['fmt_ctime'] = fmtTime($e['ctime']);
			$e['fmt_size']  = fmtSize($e['size']);
			$e['fmt_count'] = "";

			if (sizeof($e['entries']) == 0) continue;
		}
		else if ($e['type'] == 'combined_pack')
		{
			usort($e['entries'], 'cmp_mtime');
			$sz = 0; foreach($e['entries'] as $sze) $sz += $sze['size'];
			$e['size']      = $sz;
			$e['ctime']     = assoc_max($e['entries'], 'ctime', 0);
			$e['mtime']     = assoc_max($e['entries'], 'mtime', 0);
			$e['count']     = count($e['entries']);
			$e['fmt_mtime'] = fmtTime($e['mtime']);
			$e['fmt_ctime'] = fmtTime($e['ctime']);
			$e['fmt_size']  = fmtSize($e['size']);
			$e['fmt_count'] = "" . $e['count'];

			if (sizeof($e['entries']) == 0) continue;
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

		$matches = []; // filename_2000-01-01_12-00-00.log
		if (preg_match('/.*([\._][12][0-9]{3}-[01][0-9]-[0-3][0-9][\-_ ][0-9]{2}[_\-:][0-9]{2}[_\-:][0-9]{2})$/', $n, $matches))
		{
			$n = substr($n, 0, strlen($n)-strlen($matches[1]));
			continue;
		}

		$matches = []; // filename_2000-01-01_12-00.log
		if (preg_match('/.*([\._][12][0-9]{3}-[01][0-9]-[0-3][0-9][\-_ ][0-9]{2}[_\-:][0-9]{2})$/', $n, $matches))
		{
			$n = substr($n, 0, strlen($n)-strlen($matches[1]));
			continue;
		}

		$matches = []; // filename_2000-01-01.log
		if (preg_match('/.*([\._][12][0-9]{3}-[01][0-9]-[0-3][0-9])$/', $n, $matches))
		{
			$n = substr($n, 0, strlen($n)-strlen($matches[1]));
			continue;
		}

		$matches = []; // filename.02.log
		if (preg_match('/.*(\.[0-9]+)$/', $n, $matches))
		{
			$n = substr($n, 0, strlen($n)-strlen($matches[1]));
			continue;
		}

		return $n;
	}

	return '?';
}

function get_pack_zipdirname($entry)
{
	$n = $entry['name'];

	$matches = []; // filename.02.log
	if (preg_match('/(?<g1>pack_([^_.\s]+_)?)([0-9]{4}-[0-9]{2}-[0-9]{2})(?<g2>\.tar(\.gz)?)$/', $n, $matches))
	{
		return $matches['g1'] . '*' . $matches['g2'];
	}

	return null;
}
