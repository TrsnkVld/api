<?php

	namespace tools;

	class Article {



		public static function getList() {


			\CModule::IncludeModule('iblock');
			$res = \CIBlockElement::GetList(
				['SORT' => 'ASC'],
				[
					'IBLOCK_ID' => \Config::IBLOCK_ID_ARTICLES,
					'ACTIVE' => 'Y',

				],
				false,
				false,
				array("ID","NAME","ACTIVE_FROM","CODE", "PREVIEW_TEXT", "PREVIEW_PICTURE"),
			);
			while( $item = $res->GetNext()){


				$item['image'] = \CFile::GetPath($item['PREVIEW_PICTURE']);
				$pubs[] = $item;
			}


			return $pubs;
		}

		public static function getItem($code) {

            if ( !$code ) return;

            \CModule::IncludeModule('iblock');
			$res = \CIBlockElement::GetList(
				['SORT' => 'ASC'],
				[
					'IBLOCK_ID' => \Config::IBLOCK_ID_ARTICLES,
					'ACTIVE' => 'Y',
					'CODE' => $code,

				],
				false,
				false,
				array("ID","NAME","ACTIVE_FROM","CODE", "PROPERTY_TEXT_1", "PROPERTY_TEXT_2", "DETAIL_PICTURE"),
			);
			$item = $res->GetNext();
			$item['image'] = \CFile::GetPath($item['DETAIL_PICTURE']);

			$res = \CIBlockElement::GetProperty(\Config::IBLOCK_ID_ARTICLES, $item['ID'], array("SORT" => "ASC"), array("CODE" => "SLIDER"));

			while ($ob = $res->GetNext()){
        		$images[] = \CFile::GetPath($ob['VALUE']);
			}
			$item['slider'] = $images;
			$item['TEXT_1'] = $item['~PROPERTY_TEXT_1_VALUE']['TEXT'];
			$item['TEXT_2'] = $item['~PROPERTY_TEXT_2_VALUE']['TEXT'];

			return $item;
		}

	}

	?>
