<?php

	namespace controllers;
	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Hot.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Ship.php";

	class HotPage extends CommonController {
		public function get() {
			 $this->json['page']['item'] = \tools\Hot::getItem($_GET['code']);

			 $this->json['page']['ships'] = \tools\Ship::getList();

		}

	}

	?>