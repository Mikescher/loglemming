<?php

function getConfig($key, $default)
{
	if (!file_exists(__DIR__ . '/config.user.php')) return $default;

	return (require (__DIR__ . '/config.user.php'))[$key];
}

function isPHPDebug() { return getConfig('debug_php', false); }

function isSLLDebug() { return getConfig('debug_sll', false); }

function getTailReloadSpeed() { return getConfig('tail_reload_speed', 3000); }

function getSLLDebugFileRead() { return getConfig('debug_file_read', ''); }

function getSLLDebugFileList() { return getConfig('debug_file_list', ''); }
