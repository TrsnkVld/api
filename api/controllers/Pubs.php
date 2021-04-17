<?php

	namespace controllers;
	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Pub.php";

	class Pubs extends CommonController {
		public function get() {
            $this->json['page']['items'] = \tools\Pub::getList();
		}

	}

	?>