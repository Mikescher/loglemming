<!doctype html>

<?php include "lib/sysinfo.php"; ?>
<?php include "lib/logwrapper.php"; ?>
<?php include "lib/util.php"; ?>

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

                if (isopen) epath.pop();

                for (let row of $(".row_entry"))
                {
                    let row_eid   = parseInt($(row).attr('data-eid'));
                    let row_epath = JSON.parse($(row).attr('data-epath'));
                    let row_eprnt = $(row).attr('data-eparent') === '' ? -1 : parseInt($(row).attr('data-eparent'));

                    if (row_eid === id)
                    {
                        // clicked folder

                        setRowOpen(row, epath.includes(row_eid) && !isopen);

                        $(row).removeClass('row_collapsed');
                    }
                    else if (epath.includes(row_eprnt))
                    {
                        // subnode of someone in path

                        setRowOpen(row, epath.includes(row_eid));

                        $(row).addClass('row_collapsed');
                        $(row).removeClass('row_collapsed');
                    }
                    else if (epath.includes(row_eid))
                    {
                        // somewhere previously in path

                        setRowOpen(row, true);

                        $(row).removeClass('row_collapsed');
                    }
                    else if (row_epath.length === 1)
                    {
                        // top level

                        setRowOpen(row, epath.includes(row_eid));

                        $(row).removeClass('row_collapsed');
                    }
                    else
                    {
                        // other entries

                        setRowOpen(row, false);

                        $(row).addClass('row_collapsed');
                    }
                }
            }

            function onFileClicked(id, name)
            {

            }

        </script>

		<h1><a href="index.php">Web logcat viewer</a></h1>

		<div class="infocontainer">
			<div class="infodiv">
				IP Address:&nbsp;<?php echo getIP(); ?>
			</div>
			<div class="infodiv">
				OS:&nbsp;<?php echo getOperatingSystem(); ?>
			</div>
			<div class="infodiv">
				Kernel:&nbsp;<?php echo getKernel(); ?>
			</div>
		</div>
		<div class="infocontainer">
			<div class="infodiv">
				Uptime:&nbsp;<?php echo getUptime(); ?>
			</div>
			<div class="infodiv">
				Boot Time:&nbsp;<?php echo getBootupTime(); ?>
			</div>
			<div class="infodiv">
				Free Space:&nbsp;<?php echo getDiskData(); ?>
			</div>
		</div>


		<div class="tablebox">
			<h2>Log files</h2>

			<table class="filetab pure-table pure-table-bordered">
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
				<?php printTableEntries($entries, [], 0); ?>
				</tbody>
			</table>
		</div>


	</body>
</html>