function generateUUID()
{
	function s4() {
		return Math.floor((1 + Math.random()) * 0x10000)
			.toString(16)
			.substring(1);
	}
	return s4() + s4() + '-' + s4() + '-' + s4() + '-' + s4() + '-' + s4() + s4() + s4();
}

function setRowOpen(row, val)
{
	if (val)
	{
		$(row).addClass('row_open');
		if ($(row).hasClass('row_dir'))
		{
			$(row).find('i.fas').removeClass('fa-folder');
			$(row).find('i.fas').addClass('fa-folder-open');
		}
	}
	else
	{
		$(row).removeClass('row_open');
		if ($(row).hasClass('row_dir'))
		{
			$(row).find('i.fas').removeClass('fa-folder-open');
			$(row).find('i.fas').addClass('fa-folder');
		}
	}
}

function onDirClicked(id, epath)
{
	let src    = $('.row_id_'+id);
	let isopen = $(src).hasClass('row_open');


	if (isopen)
	{
		// CLOSE

		setRowOpen(src, false);

		for (let row of $(".row_entry"))
		{
			let row_eid   = parseInt($(row).attr('data-eid'));
			let row_epath = JSON.parse($(row).attr('data-epath'));

			if (row_eid === id)
			{
				$(row).removeClass('row_collapsed');
			}
			else if (row_epath.includes(id))
			{
				setRowOpen(row, false);
				$(row).addClass('row_collapsed');
			}
		}
	}
	else
	{
		// OPEN

		setRowOpen(src, true);

		for (let row of $(".row_entry"))
		{
			let row_eprnt = $(row).attr('data-eparent') === '' ? -1 : parseInt($(row).attr('data-eparent'));

			if (row_eprnt === id) $(row).removeClass('row_collapsed');
		}
	}
}

function onBackClicked()
{
	autoReloadEnabled = false;
	updateBtnReload();

	$('.tablebox').css('visibility', 'visible');
	$('.tablebox').css('display', 'flex');
	$('.logviewbox').css('visibility', 'collapse');
	$('.logviewbox').css('display', 'none');
}

function onToggleReloadClicked()
{
	autoReloadEnabled = !autoReloadEnabled;
	updateBtnReload();
}

function updateBtnReload()
{
	if (!autoReloadEnabled)
	{
		$('#btnReload').removeClass('btnReload_error');
		$('#btnReload').removeClass('btnReload_active');
		$('#btnReload_spinner').removeClass('fa-spin');
		$('#btnReload').removeClass('btnReload_enabled');
		return;
	}
	else
	{
		$('#btnReload').removeClass('btnReload_error');
		$('#btnReload').removeClass('btnReload_active');
		$('#btnReload_spinner').removeClass('fa-spin');
		$('#btnReload').removeClass('btnReload_enabled');
		$('#btnReload').addClass('btnReload_enabled');
	}
}

function autoReload()
{
	if (selectedPath == '') autoReloadEnabled = false;

	if (!autoReloadEnabled)
	{
		$('#btnReload').removeClass('btnReload_error');
		$('#btnReload').removeClass('btnReload_active');
		$('#btnReload_spinner').removeClass('fa-spin');
		$('#btnReload').removeClass('btnReload_enabled');
		setTimeout(autoReload, RELOAD_SPEED);
		return;
	}

	$('#btnReload').removeClass('btnReload_enabled');
	$('#btnReload').addClass('btnReload_active');
	$('#btnReload_spinner').addClass('fa-spin');

	let sendPath = selectedPath;

	$
		.ajax({ url: "ajax/getlog.php?path="+encodeURIComponent(selectedPath) })
		.done(function(msg)
	{
		if (!autoReloadEnabled) { setTimeout(autoReload, RELOAD_SPEED); updateBtnReload(); return; }
		if (sendPath != selectedPath) { setTimeout(autoReload, RELOAD_SPEED); updateBtnReload(); return; }

		$('#logviewcontent').html(msg);

		$('#btnReload').removeClass('btnReload_active');
		$('#btnReload_spinner').removeClass('fa-spin');
		$('#btnReload').addClass('btnReload_enabled');

		setTimeout(autoReload, RELOAD_SPEED);

	})
		.fail(function(msg)
	{
		if (!autoReloadEnabled) { setTimeout(autoReload, RELOAD_SPEED); updateBtnReload(); return; }
		if (sendPath != selectedPath) { setTimeout(autoReload, RELOAD_SPEED); updateBtnReload(); return; }

		$('#btnReload').removeClass('btnReload_active');
		$('#btnReload').addClass('btnReload_error');
		$('#btnReload_spinner').removeClass('fa-spin');

		setTimeout(autoReload, RELOAD_SPEED);
	});
}

function findAlt(dat, path)
{
	for (let e of dat)
	{
		if ('files' in e)
		{
			for(let f of e.files)
			{
				if (f.path == path) return e.files;
			}
		}
		else if ('entries' in e)
		{
			let recr = findAlt(e.entries, path);
			if (recr != null) return recr;
		}

	}
	return null;
}

function onFileClicked(path, display)
{
	parent.location.hash = path;
	selectedPath = path;
	autoReloadEnabled = false;
	updateBtnReload();

	$('#alt_list').html('');
	let alt = findAlt(DATA, path);
	if (alt != null)
	{
		for (let d of alt)
		{
			if (d.path == path)
			{
				let str = '<div class="alt_list_elem alt_list_elem_selected">' + d.name + '</div>';
				$('#alt_list').append(str);
			}
			else
			{
				let uuid = generateUUID();
				let str = '<div class="alt_list_elem" id="'+uuid+'">' + d.name + '</div>';
				$('#alt_list').append(str);
				$('#'+uuid).click(function () { onFileClicked(d.path, display); });
			}
		}
	}


	$('.tablebox').css('visibility', 'collapse');
	$('.tablebox').css('display', 'none');
	$('.logviewbox').css('visibility', 'visible');
	$('.logviewbox').css('display', 'block');

	$('#logviewtitle_content').html(display);
	$('#logviewcontent').html('loading...');
	$.ajax({
		url: "ajax/getlog.php?path="+encodeURIComponent(path)
	}).done(function(msg)
	{
		$('#logviewcontent').html(msg);
	});
}

function printTableEntries(entries, fpath, path, indent)
{
	let result = "";

	for (entry of entries)
	{
		if (entry.type == 'file' || entry.type == 'compressed_file')
		{
			let eparent  = path.length == 0 ? '' : path[(path.length-1];
			let eid      = entry.id;
			let cssclass = 'row_entry row_file row_id_' + eid + ' ' . ((indent>0)?'row_collapsed' : '');
			let onclick  = 'onFileClicked("' + entry.files[0].path + '", "' + $entry.path + '");';
			let epath    = '[' + path.concat([eid]).join(', ') + ']';

			result += ("<tr class='"+cssclass+"' onclick='"+onclick"+' data-epath='"+epath+"' data-eid='"+eid+"' data-eparent='"+eparent"+'>");
			result += ('<td class="td_name">');
			for ($i=0;$i<$indent;$i++) result += ('<span class="row_name_indent"></span>');
			result += ('<i class="fas ' + ($entry['gzip'] ? 'fa-file-archive' : 'fa-file') + '"></i>' + $entry.name);
			result += ('</td>');
			result += ('<td>' + entry.fmt_size     + '</td>');
			result += ('<td>' + entry.files.length + '</td>');
			result += ('<td>' + entry.fmt_ctime    + '</td>');
			result += ('<td>' + entry.fmt_mtime    + '</td>');
			result += ('</tr>');
			result += ("\n");
		}
		else if (entry.type == 'dir' || entry.type == 'compressed_dir')
		{
			let eparent  = path.length == 0 ? '' : path[(path.length-1];
			let eid      = entry.id;
			let cssclass = 'row_entry row_dir row_id_' + eid . ' ' + ((indent>0)?'row_collapsed' : '');
			let epath    = '[' + path.concat([eid]).join(', ') + ']';
			let onclick  = 'onDirClicked(' + entry.id + ', '  + epath + ');';

			result += ("<tr class='"+cssclass+"' onclick='"+onclick"+' data-epath='"+epath+"' data-eid='"+eid+"' data-eparent='"+eparent"+'>");
			result += ('<td class="td_name">');
			for ($i=0;$i<$indent;$i++) result += ('<span class="row_name_indent"></span>');
			result += ('<i class="fas fa-folder"></i>' + entry.name);
			result += ('</td>');
			result += ('<td>' + entry.fmt_size     + '</td>');
			result += ('<td></td>');
			result += ('<td>' + entry.fmt_ctime    + '</td>');
			result += ('<td>' + entry.fmt_mtime    + '</td>');
			result += ('</tr>');
			result += ("\n");

			result += printTableEntries(entry.entries, fpath.concat([entry.name]), path.concat([entry.id]), $indent+1);
		}
	}

	return result;
}

function getTableHTML()
{
	let result = "";

	result += "<thead>\n";
	result += "<tr>\n";
	result += "<th style='width: 600px'>Name</th>\n";
	result += "<th style='width: 100px'>Size</th>\n";
	result += "<th style='width: 100px'>Rotation</th>\n";
	result += "<th style='width: 150px'>Created</th>\n";
	result += "<th style='width: 150px'>Modified</th>\n";
	result += "</tr>\n";
	result += "</thead>\n";
	result += "<tbody>\n";
	result += printTableEntries(DATA, [], [], 0);
	result += "</tbody>\n";

	return result;

}

//#############################################################################

window.onload = function ()
{
	$('#loglistcontent').html( getTableHTML() );

	if (parent.location.hash != '')
	{
		let path = parent.location.hash.substring(1);
		onFileClicked(path, path);
	}

	setTimeout(autoReload, RELOAD_SPEED);
}