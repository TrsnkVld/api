<?php

	namespace controllers;

	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/User.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Order.php";

	class ShareSave extends CommonController {
		public function post() {

			\CModule::IncludeModule('iblock');

			$params = self::postParams();
			//d($params);

			// проверить валидность заказа
			$id = decode($params['orderHash']);
			$order = \tools\Order::byId($id);
			if (!$order) throw new \Exception("Order not found by id " . $id);

			$this->sendEmail($params['email'], $params['orderHash']);

			$this->json['page'] = [
				"email" => $params['email'],
			];
		}

		protected function sendEmail($email, $hash) {

			$arEventFields = array(
				"EMAIL_FROM" => \Config::EMAIL_FROM,
				"EMAIL_TO" => $email,
				'ORDER_LINK' => \tools\Order::linkForHash($hash),
				'SITE_NAME' => $_SERVER['HTTP_HOST'],
			);

			$arrSite = 's1';

			$send = \CEvent::Send('SHARE_SAVE', $arrSite, $arEventFields, 'N');
		}
	}
?>
