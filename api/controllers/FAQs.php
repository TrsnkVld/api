<?php

	namespace controllers;
	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/FAQ.php";

	class FAQs extends CommonController {
		public function get() {
			 \CModule::IncludeModule('iblock');


			 $this->json['page']['items'] =  \tools\FAQ::getList();
		}

	}

	?>