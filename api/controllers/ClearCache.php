<?php

	namespace controllers;
	require_once "CommonController.php";

	class ClearCache extends CommonController {

		public $withCache = FALSE;

		public function get() {
			//parent::get();
			ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT);
			ini_set('display_errors',  1);
			ini_set('display_startup_errors', 1);

			$path = $_SERVER['DOCUMENT_ROOT'] . '/upload/';

			print "Удаляем кэш в: ".$path."\n";

			$files = array();
			if ($dir = opendir($path))  {
				while (false !== ($file = readdir($dir))) {
					if ($file == "." || $file == ".." || (is_dir($path.$file))) continue;
					if(substr($file, 0, 10) == 'json.cache') {
						unlink($path.$file);
						$files[] = $file;
					}
				}
				closedir($dir);
			}

			print 'Удалено файлов: ' . count($files);

		}
	}