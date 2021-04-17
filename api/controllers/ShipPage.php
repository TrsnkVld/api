<?php

	namespace controllers;
	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Ship.php";

	class ShipPage extends CommonController {
		public function get() {
			 $this->json['page']['item'] = \tools\Ship::getItem($_GET['code']);

		}

	}

	?>