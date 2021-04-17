<?php

	namespace tools;

	class Source {



		public static function getList() {


			\CModule::IncludeModule('iblock');
			$res = \CIBlockElement::GetList(
				['SORT' => 'ASC'],
				[
					'IBLOCK_ID' => \Config::IBLOCK_ID_SOURCE,
					'ACTIVE' => 'Y',

				],
				false,
				false,
				array("ID","NAME","CODE", "PREVIEW_EMAIL"),
			);
			while( $item = $res->GetNext()){


				$sources[] = $item;
			}


			return $sources;
		}


	}

	?>
