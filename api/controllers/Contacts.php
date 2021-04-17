<?php

	namespace controllers;
	require_once "CommonController.php";

// Event: NEW_TECH_MESSAGE
// ID Почтового шаблона: 346

	class Contacts extends CommonController {
//    public $withCache = false;
		/*public function post() {
			\CModule::IncludeModule('iblock');
			$post = $this->postParams();

			$arSend = array(
				'NAME' => $post['name'],
				'EMAIL_FROM' => $post['email'],
				'PHONE' => $post['phone'],
				'TEXT' => $post['message'],
			);

			$id = \CEvent::Send('NEW_CONTACT_MESSAGE', 's1', $arSend);

			$this->json['page']['status'] = [
				'status' => 'OK',
				'id' => $id,
			];
		}*/
	}
