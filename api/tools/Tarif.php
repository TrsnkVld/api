<?php

	namespace tools;

	class Tarif {



		public static function getList() {


			\CModule::IncludeModule('iblock');
			$rsOffers = \CIBlockElement::GetList(['SORT' => 'PROPERTY_TIME'],[
					'IBLOCK_ID' => \Config::IBLOCK_ID_SHIP_TP,
					'ACTIVE' => 'Y',
					//'CML2_LINK' => $item['ID'],

				],
			false,
			false,array("ID","NAME","PROPERTY_TYPE","PROPERTY_TIME", "PROPERTY_DAYS","PROPERTY_SEASON", "PROPERTY_CML2_LINK")
			);
				// min price for type people
			while($arOffer = $rsOffers->GetNext())
			{
					//$offer_price[$arOffer["PROPERTY_TYPE_VALUE"]][] = \GetCatalogProductPrice($arOffer["ID"], 1)['PRICE'];
                    $arOffer['price'] = \GetCatalogProductPrice($arOffer["ID"], 1)['PRICE'];
                    $offers[] = $arOffer;
			}

			$tarifs['offers'] = $offers;

			$res = \CIBlockElement::GetProperty(\Config::IBLOCK_ID_CONTENT, \Config::ID_TARIFS, array("SORT" => "ASC"), array("CODE" => "SLIDER"));
				//$ar_props = $db_props->Fetch();

			while ($ob = $res->GetNext()){
        		$images[] = \CFile::GetPath($ob['VALUE']);
			}
        	$tarifs['slider'] = $images;


			return $tarifs;
		}



	}

	?>
