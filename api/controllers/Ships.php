<?php

	namespace controllers;
	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Ship.php";

	class Ships extends CommonController {
		public function get() {
			$this->json['page']['items'] = \tools\Ship::getList();

			$res = \CIBlockElement::GetProperty(\Config::IBLOCK_ID_CONTENT, \Config::ID_SHIPS, array("SORT" => "ASC"), array("CODE" => "SLIDER"));
				//$ar_props = $db_props->Fetch();

			while ($ob = $res->GetNext()){
        		$images[] = \CFile::GetPath($ob['VALUE']);
			}

			$res = \CIBlockElement::GetList(array(),['IBLOCK_ID' => \Config::IBLOCK_ID_CONTENT,'ID' => \Config::ID_SHIPS,'ACTIVE' => 'Y',],
				false,
				false,
				array("ID","PROPERTY_FULL_1","PROPERTY_FULL_2", "PROPERTY_FULL_3", "PROPERTY_FULL_4")
				);
	            $tech = $res->GetNext();

	        $this->json['page']['FULL_1'] =  $tech['~PROPERTY_FULL_1_VALUE']['TEXT'];
	        $this->json['page']['FULL_2'] =  $tech['~PROPERTY_FULL_2_VALUE']['TEXT'];
	        $this->json['page']['FULL_3'] =  $tech['~PROPERTY_FULL_1_VALUE']['TEXT'];
	        $this->json['page']['FULL_4'] =  $tech['~PROPERTY_FULL_2_VALUE']['TEXT'];


        	$this->json['page']['slider'] = $images;

		}

	}

	?>