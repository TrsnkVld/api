<?php

	namespace controllers;
	require_once "CommonController.php";

	class Gallery extends CommonController {

  			public function get() {
                \CModule::IncludeModule('iblock');
				$res = \CIBlockElement::GetList(
					['SORT' => 'ASC'],
					[
						'ID' => \Config::ID_GALLERY,
						'IBLOCK_ID' => \Config::IBLOCK_ID_CONTENT,
						'ACTIVE' => 'Y',


					],
					false,
					false,
					array("ID","NAME", "DETAIL_TEXT"),
				);

				while( $item = $res->GetNext()){

					$images = array();
					//print_r($item);exit;
					$res = \CIBlockElement::GetProperty(\Config::IBLOCK_ID_CONTENT, $item['ID'], array("SORT" => "ASC"), array("CODE" => "SLIDER"));
					while ($ob = $res->GetNext()){
	        			$images[] = \CFile::GetPath($ob['VALUE']);
					}
                    $item['images'] = $images;

                    $res = \CIBlockElement::GetProperty(\Config::IBLOCK_ID_CONTENT, $item['ID'], array("SORT" => "ASC"), array("CODE" => "SLIDER_INFO"));
					while ($ob = $res->GetNext()){
	        			$imagesInfo[] = $ob['VALUE'];
					}
                    $item['images_info'] = $imagesInfo;

					$items[] = $item;
				}

				 $this->json['page']['content'] = $items;

			}
	}

?>