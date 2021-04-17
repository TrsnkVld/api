<?php

	namespace tools;

	class Hot {



		public static function getList() {


			\CModule::IncludeModule('iblock');
			$res = \CIBlockElement::GetList(
				['SORT' => 'ASC'],
				[
					'IBLOCK_ID' => \Config::IBLOCK_ID_HOTS,
					'ACTIVE' => 'Y',

				],
				false,
				false,
				array("ID","NAME","ACTIVE_FROM","CODE", "PREVIEW_TEXT", "PREVIEW_PICTURE", "PROPERTY_PRICE"),
			);
			while( $item = $res->GetNext()){


				$item['image'] = \CFile::GetPath($item['PREVIEW_PICTURE']);
				$hots[] = $item;
			}


			return $hots;
		}

		public static function getItem($code) {

            if ( !$code ) return;

            \CModule::IncludeModule('iblock');
			$res = \CIBlockElement::GetList(
				['SORT' => 'ASC'],
				[
					'IBLOCK_ID' => \Config::IBLOCK_ID_HOTS,
					'ACTIVE' => 'Y',
					'CODE' => $code,

				],
				false,
				false,
				array("ID","NAME","ACTIVE_FROM","CODE", "PROPERTY_PRICE", "DETAIL_TEXT", "DETAIL_PICTURE", "PROPERTY_PRICE_EC", "PROPERTY_PRICE_ST", "PROPERTY_PRICE_LU", "PROPERTY_TEXT_1", "PROPERTY_TEXT_2"),
			);
			$item = $res->GetNext();
			$res = \CIBlockElement::GetProperty(\Config::IBLOCK_ID_HOTS, $item['ID'], array("SORT" => "ASC"), array("CODE" => "SLIDER"));
			$item['TEXT_1'] = $item['~PROPERTY_TEXT_1_VALUE']['TEXT'];
			$item['TEXT_2'] = $item['~PROPERTY_TEXT_2_VALUE']['TEXT'];
            $item['image'] = \CFile::GetPath($item['DETAIL_PICTURE']);

			while ($ob = $res->GetNext()){
        		$images[] = \CFile::GetPath($ob['VALUE']);
			}
			$item['slider'] = $images;

			return $item;
		}

	}

	?>
