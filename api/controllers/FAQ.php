<?php

	namespace controllers;
	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Ship.php";

	class FAQ extends CommonController {
		public function get() {
			 \CModule::IncludeModule('iblock');
			$res = \CIBlockElement::GetList(
				['SORT' => 'ASC'],
				[
					'IBLOCK_ID' => \Config::IBLOCK_ID_FAQ,
					'ACTIVE' => 'Y',

				],
				false,
				false,
				array("ID","NAME",/*"ACTIVE_FROM",*/ "DETAIL_TEXT", "PROPERTY_EMAIL"),
			);
			while( $item = $res->GetNext()){
				// other props...
				$faq[] = $item;
			}

			 $this->json['page']['faq'] = $faq;
		}

	}

	?>