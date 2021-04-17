<?php

	namespace tools;

	class Excursion {



		public static function getList() {

            \CModule::IncludeModule('iblock');
            \CModule::IncludeModule('sale');


			$res = \CIBlockElement::GetList(
				['SORT' => 'ASC'],
				[
					'IBLOCK_ID' => \Config::IBLOCK_ID_EXCS,
					'ACTIVE' => 'Y',

				],
				false,
				false,
				array("ID","NAME","CODE", "PREVIEW_PICTURE", "PROPERTY_TIME", "PROPERTY_IS_HIT", "PROPERTY_HOT", "PROPERTY_ICON", "PROPERTY_SHIP", "PROPERTY_PROGRAM_TEXT", "PROPERTY_PRICHAL" ,"PROPERTY_COORD_ITEM", "PROPERTY_COORD_WAY"),
			);
			while( $item = $res->GetNext()){

                $item['image'] = \CFile::GetPath($item['PREVIEW_PICTURE']);

                $rsOffers = \CIBlockElement::GetList(['SORT' => 'ASC'],[
					'IBLOCK_ID' => \Config::IBLOCK_ID_EXCS_TP,
					'ACTIVE' => 'Y',
					'PROPERTY_CML2_LINK' => $item['ID'],
				],
				false,
				false,array("ID","NAME","PROPERTY_TYPE","PROPERTY_TIME")
				);
				$offers = array();
				while($arOffer = $rsOffers->GetNext())
				{
					$offer_price[] = \GetCatalogProductPrice($arOffer["ID"], 1)['PRICE'];
					$offers[] = $arOffer;
				}

                $item['offers'] = $offers;
                $item['minPrice'] = min($offer_price);
                $item['ICON'] = $item['~PROPERTY_ICON_VALUE']['TEXT'];
                $item['COORD_WAY'] = $item['~PROPERTY_COORD_WAY_VALUE']['TEXT'];
				$item['COORD_ITEM'] = $item['~PROPERTY_COORD_ITEM_VALUE']['TEXT'];
				$item['PROGRAM_TEXT'] = $item['~PROPERTY_PROGRAM_TEXT_VALUE']['TEXT'];
                //d($item);

				$excursions[] = $item;
			}

			return $excursions;
		}

		public static function getItem($code) {
            if ( !$code ) return;

             \CModule::IncludeModule('iblock');
            \CModule::IncludeModule('sale');



			$res = \CIBlockElement::GetList(
				['SORT' => 'ASC'],
				[
					'IBLOCK_ID' => \Config::IBLOCK_ID_EXCS,
					'ACTIVE' => 'Y',
					'CODE' => trim($code),

				],
				false,
				false,
				array("ID","NAME", "CODE", "PROPERTY_H2", "PROPERTY_IMAGE_TOP", "PROPERTY_TIME", "PROPERTY_IS_HIT", "PROPERTY_HOT", "PROPERTY_PRICHAL","PROPERTY_PRICHAL","PROPERTY_SHIP", "PROPERTY_PROGRAM_TEXT","PROPERTY_FULL_1","PROPERTY_FULL_2", "PROPERTY_SLIDER","PROPERTY_COORD_ITEM", "PROPERTY_COORD_WAY"),
			);
				$item = $res->GetNext();

				$images = array();
				$res = \CIBlockElement::GetProperty(\Config::IBLOCK_ID_EXCS, $item['ID'], array("SORT" => "ASC"), array("CODE" => "SLIDER"));

				while ($ob = $res->GetNext()){
        			$images[] = \CFile::GetPath($ob['VALUE']);
				}

                $item['images'] = $images;

                $rsOffers = \CIBlockElement::GetList(['SORT' => 'PROPERTY_TIME'],[
					'IBLOCK_ID' => \Config::IBLOCK_ID_EXCS_TP,
					'ACTIVE' => 'Y',
					'PROPERTY_CML2_LINK' => $item['ID'],

				],
				false,
				false,array("ID","NAME","PROPERTY_TYPE","PROPERTY_TIME")
				);
				// min price for type people
				while($arOffer = $rsOffers->GetNext())
				{
					$offer_price[$arOffer["PROPERTY_TYPE_VALUE"]][] = \GetCatalogProductPrice($arOffer["ID"], 1)['PRICE'];
                    $offers[] = $arOffer;
				}

                $item['offers'] = $offers;
                $item['PROGRAM_TEXT'] = $item['~PROPERTY_PROGRAM_TEXT_VALUE']['TEXT'];
				$item['FULL_1'] = $item['~PROPERTY_FULL_1_VALUE']['TEXT'];
				$item['FULL_2'] = $item['~PROPERTY_FULL_2_VALUE']['TEXT'];
				$item['COORD_WAY'] = $item['~PROPERTY_COORD_WAY_VALUE']['TEXT'];
				$item['COORD_ITEM'] = $item['~PROPERTY_COORD_ITEM_VALUE']['TEXT'];

				$res = \CIBlockElement::GetList(array(),['IBLOCK_ID' => \Config::IBLOCK_ID_CONTENT,'ID' => \Config::ID_EX,'ACTIVE' => 'Y',],
				false,
				false,
				array("ID","PROPERTY_FULL_1","PROPERTY_FULL_2")
				);
            	$tech = $res->GetNext();

				$item['FULL_3'] =  $tech['~PROPERTY_FULL_1_VALUE']['TEXT'];
            	$item['FULL_4'] =  $tech['~PROPERTY_FULL_2_VALUE']['TEXT'];
                //$item['times'] = $times;
                $item['image'] = \CFile::GetPath($item['PROPERTY_IMAGE_TOP_VALUE']);
                unset($item['PROPERTY_IMAGE_TOP_VALUE']);
                $item['minPrice'] = json_encode($offer_price);

                // get time for tp Id order






			return $item;
		}

	}

	?>
