<?php

	namespace controllers;
	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Source.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Specialist.php";

	class SpecialistList extends CommonController {
		public function get() {
			$sources = \tools\Source::getList();

			foreach ($sources as $source) {            	$items = \tools\Specialist::getList(array("SOURCE" => $source['ID']));
            	foreach ($items as $spec){            		$source['specialist'][] = $spec;            	}
            	$this->json['page']['items'][] = $source;			}

			$res = \CIBlockElement::GetProperty(\Config::IBLOCK_ID_CONTENT, \Config::ID_SPECIALIST, array("SORT" => "ASC"), array("CODE" => "SLIDER"));


			while ($ob = $res->GetNext()){
        		$images[] = \CFile::GetPath($ob['VALUE']);
			}

			$res = \CIBlockElement::GetProperty(\Config::IBLOCK_ID_CONTENT, \Config::ID_SPECIALIST, array("SORT" => "ASC"), array("CODE" => "SLIDER_INFO"));
			while ($ob = $res->GetNext()){
	        	$imagesInfo[] = $ob['VALUE'];
			}


			$res = \CIBlockElement::GetList(array(),['IBLOCK_ID' => \Config::IBLOCK_ID_CONTENT,'ID' => \Config::ID_SPECIALIST,'ACTIVE' => 'Y',],
				false,
				false,
				array("ID","PROPERTY_FULL_1","PROPERTY_FULL_2","PROPERTY_FULL_3","PROPERTY_FULL_4")
				);
			$tech = $res->GetNext();
			$this->json['page']['FULL_1'] =  $tech['~PROPERTY_FULL_1_VALUE']['TEXT'];
            $this->json['page']['FULL_2'] =  $tech['~PROPERTY_FULL_2_VALUE']['TEXT'];
   			$this->json['page']['FULL_3'] =  $tech['~PROPERTY_FULL_3_VALUE']['TEXT'];
            $this->json['page']['FULL_4'] =  $tech['~PROPERTY_FULL_4_VALUE']['TEXT'];


        	$this->json['page']['slider'] = $images;
        	$this->json['page']['slider_title'] = $imagesInfo;
		}

	}

	?>