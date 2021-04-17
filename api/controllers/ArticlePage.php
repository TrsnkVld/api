<?php

	namespace controllers;
	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Article.php";

	class ArticlePage extends CommonController {
		public function get() {
			 $this->json['page']['item'] = \tools\Article::getItem($_GET['code']);

		}

	}

	?>