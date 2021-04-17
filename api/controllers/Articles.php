<?php

	namespace controllers;
	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Article.php";

	class Articles extends CommonController {
		public function get() {
            $this->json['page']['items'] = \tools\Article::getList();
		}

	}

	?>