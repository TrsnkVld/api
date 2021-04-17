<?php

	namespace controllers;
	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Ship.php";

	class Specialist extends CommonController {
		public function get() {
			 \CModule::IncludeModule('iblock');
			$res = \CIBlockElement::GetList(
				['SORT' => 'ASC'],
				[
					'IBLOCK_ID' => \Config::IBLOCK_ID_SPECIALIST,
					'ACTIVE' => 'Y',

				],
				false,
				false,
				array("ID","NAME",/*"ACTIVE_FROM",*/ /*"PREVIEW_TEXT",*/ "PREVIEW_PICTURE", "PROPERTY_EMAIL", "PROPERTY_POST"),
			);
			while( $item = $res->GetNext()){


				$item['image'] = \CFile::GetPath($item['PREVIEW_PICTURE']);
				unset($item['PREVIEW_PICTURE']);
				$specialist[] = $item;
			}

			 $this->json['page']['specialist'] = $specialist;
		}

	}

	?>