<?php

	namespace tools;

	class FAQ {



		public static function getList() {


			\CModule::IncludeModule('iblock');
			$res = \CIBlockElement::GetList(
				['SORT' => 'ASC'],
				[
					'IBLOCK_ID' => \Config::IBLOCK_ID_FAQ,
					'ACTIVE' => 'Y',

				],
				false,
				false,
				array("ID","NAME",/*"ACTIVE_FROM",*/ "DETAIL_TEXT", "PROPERTY_EMAIL", "PREVIEW_TEXT"),
			);
			while( $item = $res->GetNext()){
				$faq[] = $item;
			}


			return $faq;
		}


	}

	?>
