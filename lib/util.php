<?php

function printTableEntries($entries, $fpath, $path, $indent)
{
	foreach ($entries as $entry)
	{
		if ($entry['type'] == 'file')
		{
			$eparent = sizeof($path)==0 ? '' : $path[sizeof($path)-1];
			$eid     = $entry['id'];
			$class   = 'row_entry row_file row_id_' . $eid . ' ' . ($indent>0?'row_collapsed' : '');
			$onclick = 'onFileClicked("' . $entry['files'][0]['path'] . '", "' . $entry['path'] . '");';
			$epath   = '[' . implode(', ', array_merge($path, [$eid])) . ']';

			print("<tr class='$class' onclick='$onclick' data-epath='$epath' data-eid='$eid' data-eparent='$eparent'>");
			print('<td class="td_name">');
			for ($i=0;$i<$indent;$i++) print('<span class="row_name_indent"></span>');
			print('<i class="fas ' . ($entry['gzip'] ? 'fa-file-archive' : 'fa-file') . '"></i>' . $entry['name']);
			print('</td>');
			print('<td>' . fmtSize($entry['size'])  . '</td>');
			print('<td>' . sizeof( $entry['files']) . '</td>');
			print('<td>' . fmtTime($entry['ctime']) . '</td>');
			print('<td>' . fmtTime($entry['mtime']) . '</td>');
			print('</tr>');
			print("\n");
		}
		elseif ($entry['type'] == 'dir')
		{
			$eparent = sizeof($path)==0 ? '' : $path[sizeof($path)-1];
			$eid     = $entry['id'];
			$class   = 'row_entry row_dir row_id_' . $eid . ' ' . ($indent>0?'row_collapsed' : '');
			$epath   = '[' . implode(', ', array_merge($path, [$eid])) . ']';
			$onclick = 'onDirClicked(' . $entry['id'] . ', '.$epath.');';

			print("<tr class='$class' onclick='$onclick' data-epath='$epath' data-eid='$eid' data-eparent='$eparent'>");
			print('<td class="td_name">');
			for ($i=0;$i<$indent;$i++)print('<span class="row_name_indent"></span>');
			print('<i class="fas fa-folder"></i>' . $entry['name']);
			print('</td>');
			print('<td></td>');
			print('<td></td>');
			print('<td></td>');
			print('<td></td>');
			print('</tr>');
			print("\n");

			printTableEntries($entry['entries'], array_merge($fpath, [$entry['name']]), array_merge($path, [$entry['id']]), $indent+1);
		}
	}
}
