<?php

	namespace controllers;

	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/User.php";

	class CallSubmit extends CommonController {
		public function post() {

			\CModule::IncludeModule('iblock');

			$params = self::postParams();

			//print_r($params);exit;

			// test params
			//$params['name'] = "test";
			//$params['phone'] = "000-00-00";





			if ( !$params['name'] || !$params['phone'] ) throw new \Exception("Error params");

            $admin = \CUser::GetList(($by = "NAME"), ($order = "desc"),array("ID" => \Config::ID_ADMIN),array('SELECT' => array("UF_*")));
            $admin = $admin->GetNext();

            //print_r($admin['EMAIL']);exit;

            $arEventFields = array(
				"EMAIL_FROM" => \Config::EMAIL_FROM,
				'NAME' => $params['name'],
				'EMAIL_TO' => $admin['EMAIL'],
				'PHONE' => $params['phone'],
				'HOST' => "http://" . $_SERVER['HTTP_HOST'] . "/auth",
				'SITE_NAME' => $_SERVER['HTTP_HOST'],
			);


            $arrSite = 's1';
			$send = \CEvent::SendImmediate('CALL', $arrSite, $arEventFields, 'N');


			if ( $send ) {
				$this->json['page']['result'] = "ok";
			}
			else {
				throw new \Exception("Error sending email");
			}

		}
	}

?>