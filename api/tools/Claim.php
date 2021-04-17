<?php

	namespace tools;

	class Claim {



		public static function getList() {


			\CModule::IncludeModule('iblock');
			$res = \CIBlockElement::GetList(
				['SORT' => 'ASC'],
				[
					'IBLOCK_ID' => \Config::IBLOCK_ID_CLAIM,
					'ACTIVE' => 'Y',

				],
				false,
				false,
				array("ID","NAME","ACTIVE_FROM","CODE", "DETAIL_TEXT", "PROPERTY_EMAIL"),
			);
			while( $item = $res->GetNext()){

				$claims[] = $item;
			}


			return $claims;
		}


	}

	?>
