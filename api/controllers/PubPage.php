<?php

	namespace controllers;
	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Pub.php";

	class PubPage extends CommonController {
		public function get() {
			 $this->json['page']['item'] = \tools\Pub::getItem($_GET['code']);

		}

	}

	?>