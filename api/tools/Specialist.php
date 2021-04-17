<?php

	namespace tools;

	class Specialist {



		public static function getList($params) {


			if ( !$params ) return false;

			\CModule::IncludeModule('iblock');
			$res = \CIBlockElement::GetList(
				['SORT' => 'ASC'],
				[
					'IBLOCK_ID' => \Config::IBLOCK_ID_SPECIALIST,
					'ACTIVE' => 'Y',
					'PROPERTY_SOURCE' => $params['SOURCE'],

				],
				false,
				false,
				array("ID","NAME","CODE", "PREVIEW_TEXT", "PREVIEW_PICTURE", "PROPERTY_EMAIL", "PROPERTY_POST"),
			);
			while( $item = $res->GetNext()){


				$item['image'] = \CFile::GetPath($item['PREVIEW_PICTURE']);
				$specialists[] = $item;
			}


			return $specialists;
		}


	}

	?>
