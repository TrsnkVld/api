<?php

	namespace controllers;
	require_once "CommonController.php";

	class Order extends CommonController {

		public function get() {
			\CModule::IncludeModule('iblock');
			parent::get();

			$this -> initMetas();

			$params = $_GET;
		}

		protected function initMetas() {
			$this->json['page']['title'] = "Order";
			$this->json['page']['description'] = "Packmaster";
			$this->json['page']['keywords'] = "Packmaster";
		}

		protected function mb_ucfirst($text) {
			return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
		}
	}