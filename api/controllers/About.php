<?php

	namespace controllers;
	require_once "CommonController.php";

	class About extends CommonController {

  			public function get() {
                \CModule::IncludeModule('iblock');
				$res = \CIBlockElement::GetList(
					['SORT' => 'ASC'],
					[
						'ID' => \Config::ID_ABOUT,
						'IBLOCK_ID' => \Config::IBLOCK_ID_CONTENT,
						'ACTIVE' => 'Y',


					],
					false,
					false,
					array("ID","NAME", "PROPERTY_FULL_1","PROPERTY_FULL_2","PROPERTY_FULL_3","PROPERTY_FULL_4"),
				);

					$item = $res->GetNext();

					$images = array();
					//print_r($item);exit;
					$res = \CIBlockElement::GetProperty(\Config::IBLOCK_ID_CONTENT, $item['ID'], array("SORT" => "ASC"), array("CODE" => "SLIDER"));
					//$ar_props = $db_props->Fetch();

					while ($ob = $res->GetNext()){
	        			$images[] = \CFile::GetPath($ob['VALUE']);
					}

					$item['images'] = $images;


					$item['FULL_1'] = $item['~PROPERTY_FULL_1_VALUE']['TEXT'];
					$item['FULL_2'] = $item['~PROPERTY_FULL_2_VALUE']['TEXT'];
					$item['FULL_3'] = $item['~PROPERTY_FULL_3_VALUE']['TEXT'];
					$item['FULL_4'] = $item['~PROPERTY_FULL_4_VALUE']['TEXT'];

					$items[] = $item;



				 $this->json['page']['content'] = $items;

			}
	}

?>