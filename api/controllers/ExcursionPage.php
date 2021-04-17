<?php

	namespace controllers;
	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Excursion.php";

	class ExcursionPage extends CommonController {
		public function get() {
			 $this->json['page']['item'] = \tools\Excursion::getItem($_GET['code']);

		}

	}

	?>