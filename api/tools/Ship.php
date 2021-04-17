<?php

	namespace tools;

	class Ship {



		public static function getList() {

            \CModule::IncludeModule('iblock');
            \CModule::IncludeModule('sale');

			$res = \CIBlockElement::GetList(
				['SORT' => 'ASC'],
				[
					'IBLOCK_ID' => \Config::IBLOCK_ID_SHIP,
					'ACTIVE' => 'Y',

				],
				false,
				false,
				array("ID",
					"NAME",
					"CODE",
					"PROPERTY_WITH_PEOPLE",
					"PROPERTY_WITH_TABLES",
					"PROPERTY_TABLES",
					"PROPERTY_WITH_TOILET",
					"PROPERTY_WITH_MUSIC",
					"PROPERTY_WITH_WIFI",
					"PROPERTY_WITH_DINNER",
					"PROPERTY_IS_HIT",
					"PROPERTY_HOT",
					"PROPERTY_CAPACITY"
					//"PROPERTY_SLIDER"
					)
			);



                $timeWeek = strtotime('+1 week');


                $timeOrder = array();
             	$dbBasketItems = \CSaleBasket::GetList(array(), array(), false, false, array());
					while ($arItems = $dbBasketItems->Fetch()) {
                        //print_r($arItems);
                        $dbOrderProps = \CSaleOrderPropsValue::GetList(
					        array("SORT" => "ASC"),
					        array("ORDER_ID" => $arItems["ID"])
					    );
					    while ($arOrderProps = $dbOrderProps->GetNext()) {

					        if ($arOrderProps['NAME'] == "Время бронирования" && strtotime($arOrderProps['VALUE_ORIG']) > time() && strtotime($arOrderProps['VALUE_ORIG']) < $timeWeek ) {
					        	$timeOrder[explode("#",$arItems['PRODUCT_XML_ID'])[0]][$arItems['ID']] = $arOrderProps['VALUE_ORIG'];
					        	break;
					        }
					    }

					}

             // print_r($timeOrder);exit;


			while( $item = $res->GetNext()){

				$images = array();
				//print_r($item);exit;
				$res2 = \CIBlockElement::GetProperty(\Config::IBLOCK_ID_SHIP, $item['ID'], array("SORT" => "ASC"), array("CODE" => "SLIDER"));
				//$ar_props = $db_props->Fetch();

				while ($ob = $res2->GetNext()){
        			$images[] = \CFile::GetPath($ob['VALUE']);
				}


				$item['images'] = $images;

                $days = array();
                foreach ( $timeOrder[$item['ID']] as $dateOrder ){

                	$day = date('w', strtotime($dateOrder));
                	if ( $days[$day] ) $days[$day] = $days[$day]+1;
                	else $days[$day] = 1;
                }
                $item['days'] = $days;

                $rsOffers = \CIBlockElement::GetList(['SORT' => 'ASC'],[
					'IBLOCK_ID' => \Config::IBLOCK_ID_SHIP_TP,
					'ACTIVE' => 'Y',
					'PROPERTY_CML2_LINK' => $item['ID'],
				],
				false,
				false,array("ID","NAME",)
				);
				while($arOffer = $rsOffers->GetNext())
				{
					$offer_price[] = \GetCatalogProductPrice($arOffer["ID"], 1)['PRICE'];
				}



                $item['minPrice'] = min($offer_price);

				$ships[] = $item;
			}

			return $ships;
		}

		public static function getItem($code) {

            if ( !$code ) return;

            \CModule::IncludeModule('iblock');

			$res = \CIBlockElement::GetList(
				['SORT' => 'ASC'],
				[
					'IBLOCK_ID' => \Config::IBLOCK_ID_SHIP,
					'ACTIVE' => 'Y',
					'CODE' => trim($code),

				],
				false,
				false,
				array("ID",
					"NAME",
					"CODE",
					"PREVIEW_PICTURE",
					"PROPERTY_WITH_PEOPLE",
					"PROPERTY_WITH_TABLES",
					"PROPERTY_TABLES",
					"PROPERTY_WITH_TOILET",
					"PROPERTY_WITH_MUSIC",
					"PROPERTY_WITH_WIFI",
					"PROPERTY_WITH_DINNER",
					"PROPERTY_IS_HIT",
					"PROPERTY_WIDTH",
					"PROPERTY_HEIGHT",
					"PROPERTY_LENGTH",
					"PROPERTY_DEEP",
					"PROPERTY_SPEED",
					"PROPERTY_CAPACITY",
					"PROPERTY_SEASON",
					"PROPERTY_STANDART_WAY",
					"PROPERTY_FULL_1",
					"PROPERTY_FULL_2",
					"PROPERTY_FRAME",
					"PROPERTY_TYPE",
					'*'
					//"PROPERTY_SLIDER"
					)
			);


			$item = $res->GetNext();


			$images = array();

			$res = \CIBlockElement::GetProperty(\Config::IBLOCK_ID_SHIP, $item['ID'], array("SORT" => "ASC"), array("CODE" => "SLIDER"));

			while ($ob = $res->GetNext()){
        		$images[] = \CFile::GetPath($ob['VALUE']);
			}


			$item['slider'] = $images;
            $item['mainImage'] = \CFile::GetPath($item['PREVIEW_PICTURE']);

            $res = \CIBlockElement::GetList(array(),['IBLOCK_ID' => \Config::IBLOCK_ID_CONTENT,'ID' => \Config::ID_SHIPS,'ACTIVE' => 'Y',],
				false,
				false,
				array("ID","PROPERTY_FULL_1","PROPERTY_FULL_2")
			);
            $tech = $res->GetNext();

            $item['FULL_1'] =  $item['~PROPERTY_FULL_1_VALUE']['TEXT'];
            $item['FULL_2'] =  $item['~PROPERTY_FULL_2_VALUE']['TEXT'];
            $item['FULL_3'] =  $tech['~PROPERTY_FULL_1_VALUE']['TEXT'];
            $item['FULL_4'] =  $tech['~PROPERTY_FULL_2_VALUE']['TEXT'];
			$item['FRAME'] = $item['~PROPERTY_FRAME_VALUE']['TEXT'];

            unset($item['PREVIEW_PICTURE']);

            $rsOffers = \CIBlockElement::GetList(['SORT' => 'PROPERTY_TIME'],[
					'IBLOCK_ID' => \Config::IBLOCK_ID_SHIP_TP,
					'PROPERTY_CML2_LINK' => $item['ID'],
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
			$item['offers'] = $offers;


			return $item;
		}

	}

	?>
