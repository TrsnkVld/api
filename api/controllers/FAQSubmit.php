<?php

	namespace controllers;

	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/User.php";

	class FAQSubmit extends CommonController {
		public function post() {

			\CModule::IncludeModule('iblock');

			$params = self::postParams();

			//print_r($params);exit;

			// test params
			/*$params['email'] = "aivanov@playnext.ru";
			$params['msg'] = "вопрос";
			$params['name'] = "Иванов Иван Иванович";
            */




			if ( !$params['email'] || !$params['msg'] || !$params['name']) throw new \Exception("Error params");


			// get user
			$email = $params['email'];
			if ($email) {
						$res = \Bitrix\Main\UserTable::getRow(array(
							'filter' => array('=EMAIL' => $email),
							'select' => array('ID'),
							'order' => array('ID' => 'desc'),
						));
						if ($res['ID']) $userId = intval($res['ID']);
			}
			if (!$userId) {
						$this->userNew = \tools\User::registerAndLogin([
							"name" => trim($params['name']),
							"email" => $email,
							"phone" => trim($params['phone']),
						]);
						$userId = $this->userNew['ID'];
			}
			if (!$userId) throw new \Exception("Could not register user");

            $el = new \CIBlockElement;
			$arFields = Array(
					"IBLOCK_ID" => \Config::IBLOCK_ID_FAQ,
					"ACTIVE" => 'N',
					"NAME" => $params['name'],
					"DETAIL_TEXT" => $params['msg'],
					"PROPERTY_VALUES" => [
						"USER" => $userId
					]
			);
			$id = $el->Add($arFields);
			if (!$id) throw new \Exception("Could not create faq: " . $el->LAST_ERROR);


			$this->json['page']['result'] = "ok"; // TODO

		}
	}

?>