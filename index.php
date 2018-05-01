<!doctype html>

<?php
include_once __DIR__ . "/config.php";

if (isPHPDebug())
{
    ini_set('display_errors', 'On');
	error_reporting(E_ALL);
}

include_once __DIR__ . "/lib/sysinfo.php";
include_once __DIR__ . "/lib/logwrapper.php";
include_once __DIR__ . "/lib/util.php";

?>
<?php  ?>
<?php  ?>

<?php

    $entries = listEntries();

?>

<html lang="en">
	<head>
		<meta charset="utf-8">
		<link rel="stylesheet" type="text/css" href="res/pure-min.css"/>
		<link rel="stylesheet" type="text/css" href="res/custom.css"/>

		<link rel="stylesheet" href="res/fa-solid.css" />
		<link rel="stylesheet" href="res/fontawesome.min.css" />
	</head>

	<body id="rootbox">
		<script src="res/jquery-3.3.1.min.js"></script>
		<script src="res/sorttable.js"></script>
		<script src="res/custom.js"></script>

        <script type="text/javascript">

            let DATA = <?php echo json_encode($entries) ?>;

            let autoReloadEnabled = false;
            let selectedPath = '';
            let RELOAD_SPEED = <?php echo getTailReloadSpeed(); ?>;

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
                for (let d of findAlt(DATA, path.split('/')))
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

            window.onload = function ()
            {
                if (parent.location.hash != '')
                {
                    let path = parent.location.hash.substring(1);
                    onFileClicked(path, path);
                }

                setTimeout(autoReload, RELOAD_SPEED);
            }

        </script>

		<h1  class="bodyrow_1 bigheader"><a href="index.php">Web logcat viewer</a></h1>

		<div class="bodyrow_2 infocontainer">
			<div class="infodiv">
				<span>IP:</span><span><?php echo getIP(); ?></span>
			</div>
			<div class="infodiv">
                <span>OS:</span><span><?php echo getOperatingSystem(); ?></span>
			</div>
			<div class="infodiv">
                <span>Uptime:</span><span><?php echo getUptime(); ?></span>
			</div>
			<div class="infodiv">
                <span>Boot Time:</span><span><?php echo getBootupTime(); ?></span>
			</div>
			<div class="infodiv">
                <span>Free Space:</span><span><?php echo getDiskData(); ?></span>
			</div>
		</div>

		<div class="bodyrow_3 tablebox">
			<h2>Log files</h2>

			<table id="loglistcontent" class="filetab pure-table pure-table-bordered">
				<thead>
					<tr>
						<th style='width: 600px'>Name</th>
						<th style='width: 100px'>Size</th>
						<th style='width: 100px'>Rotation</th>
						<th style='width: 150px'>Created</th>
						<th style='width: 150px'>Modified</th>
					</tr>
				</thead>
				<tbody>
				<?php printTableEntries($entries, [], [], 0); ?>
				</tbody>
			</table>
		</div>

        <div class="bodyrow_3 logviewbox">
            <h2  id="logviewtitle">
                <span class="btnBack" onclick="onBackClicked();"><i class="fas fa-backward"></i></span>
                <span id="logviewtitle_content"></span>
                <span id="btnReload" class="" onclick="onToggleReloadClicked();"><i id="btnReload_spinner" class="fas fa-sync-alt"></i></span>
            </h2>
            <div id="alt_list"></div>
            <div id="logviewcontent"></div>
        </div>

	</body>
</html>