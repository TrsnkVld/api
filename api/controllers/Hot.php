<?php

	namespace controllers;
	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Ship.php";

	class Hot extends CommonController {
		public function get() {
			 \CModule::IncludeModule('iblock');
			$res = \CIBlockElement::GetList(
				['SORT' => 'ASC'],
				[
					'IBLOCK_ID' => \Config::IBLOCK_ID_HOTS,
					'ACTIVE' => 'Y',

				],
				false,
				false,
				array("ID","NAME",/*"ACTIVE_FROM",*/ /*"PREVIEW_TEXT",*/ "PREVIEW_PICTURE", "PROPERTY_PRICE"),
			);
			while( $item = $res->GetNext()){


				$item['image'] = \CFile::GetPath($item['PREVIEW_PICTURE']);
				unset($item['PREVIEW_PICTURE']);
				$hots[] = $item;
			}

			 $this->json['page']['hots'] = $hots;
		}

	}

	?>