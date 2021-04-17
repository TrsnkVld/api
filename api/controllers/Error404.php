<?php

	namespace controllers;

    require_once "CommonController.php";

	class Error404 extends CommonController {

		public function get() {
			parent::get();
			$this->json['pageData']['title'] = "Страница не найдена";
			$this->json['pageData']['description'] = "Страница не найдена";
			$this->json['pageData']['keywords'] = '';

			$this->initPopularProducts();
		}

	}