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

$entries = listEntries();

?>

<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="robots" content="noindex">

		<link rel="stylesheet" type="text/css" href="res/pure-min.css"/>
		<link rel="stylesheet" type="text/css" href="res/custom.css"/>

		<link rel="stylesheet" href="res/fa-solid.css" />
		<link rel="stylesheet" href="res/fontawesome.min.css" />
	</head>

	<body id="rootbox">
		<script src="res/jquery-3.3.1.min.js"></script>
		<script src="res/sorttable.js"></script>

        <script type="text/javascript">
            let DATA = <?php echo json_encode($entries) ?>;

            let autoReloadEnabled = false;
            let selectedPath = '';
            let RELOAD_SPEED = <?php echo getTailReloadSpeed(); ?>;
        </script>

		<script src="res/custom.js"></script>

		<h1  class="bodyrow_1 bigheader"><a href="index.php">Log Lemming</a></h1>

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
			<!-- filled via js -->
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
