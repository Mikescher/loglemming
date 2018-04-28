<!doctype html>

<?php
include "config.php";

if (isPHPDebug())
{
    ini_set('display_errors', 'On');
	error_reporting(E_ALL);
}

include "lib/sysinfo.php";
include "lib/logwrapper.php";
include "lib/util.php";

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
                $('.tablebox').css('visibility', 'visible');
                $('.tablebox').css('display', 'flex');
                $('.logviewbox').css('visibility', 'collapse');
                $('.logviewbox').css('display', 'none');
            }

            function onFileClicked(id, name, path, display)
            {
                $('.tablebox').css('visibility', 'collapse');
                $('.tablebox').css('display', 'none');
                $('.logviewbox').css('visibility', 'visible');
                $('.logviewbox').css('display', 'block');

                $('#logviewtitle').html(display);
                $('#logviewcontent').html('loading...');
                $.ajax({
                    url: "ajax/getlog.php?path="+encodeURIComponent(path)
                }).done(function(msg)
                {
                    $('#logviewcontent').html(msg);
                });
            }

        </script>

		<h1><a href="index.php">Web logcat viewer</a></h1>

		<div class="infocontainer">
			<div class="infodiv">
				IP:&nbsp;<?php echo getIP(); ?>
			</div>
			<div class="infodiv">
				OS:&nbsp;<?php echo getOperatingSystem(); ?>
			</div>
			<div class="infodiv">
				Kernel:&nbsp;<?php echo getKernel(); ?>
			</div>
			<div class="infodiv">
				Uptime:&nbsp;<?php echo getUptime(); ?>
			</div>
			<div class="infodiv">
				Boot Time:&nbsp;<?php echo getBootupTime(); ?>
			</div>
			<div class="infodiv">
				Space:&nbsp;<?php echo getDiskData(); ?>
			</div>
		</div>


		<div class="tablebox">
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

        <div class="logviewbox">
            <h2 id="logviewtitle"></h2>

            <div id="logviewcontent">

            </div>
        </div>


	</body>
</html>