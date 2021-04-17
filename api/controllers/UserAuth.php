<?php

	namespace controllers;

    require_once "CommonController.php";

	class UserAuth extends CommonController {
		public $withCache = FALSE;

		/*
		 * Принимает массив form с полями:
		 * login: string,
		 * password: string,
		 * remember: boolean
		 *
		 * Возвращает:
		 * status: ok | error
		 * error?: string
		 */
		public function post() {
			$post = $this->postParams();

			GLOBAL $USER;

			$arAuthResult = $USER->Login($post['form']['login'], $post['form']['password'], ($post['form']['remember'] ? 'Y' : 'N'));

			if (isset($arAuthResult['TYPE']) && $arAuthResult['TYPE'] == 'ERROR') {
				$this->json['status'] = 'error';
				$this->json['error'] = str_replace("<br>", " ", $arAuthResult['MESSAGE']);
			} else {
				$this->json['status'] = 'ok';
			}
		}

	}