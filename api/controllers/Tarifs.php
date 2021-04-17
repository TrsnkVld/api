<?php

	namespace controllers;
	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Ship.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Tarif.php";

	class Tarifs extends CommonController {
		public function get() {
			$this->json['page']['ships'] = \tools\Ship::getList();

			$this->json['page']['items'] = \tools\Tarif::getList();

			$res = \CIBlockElement::GetList(
					['SORT' => 'ASC'],
					[
						'ID' => \Config::ID_TARIFS,
						'IBLOCK_ID' => \Config::IBLOCK_ID_CONTENT,
						'ACTIVE' => 'Y',


					],
					false,
					false,
					array("ID","NAME", "PROPERTY_FULL_1","PROPERTY_FULL_2","PROPERTY_FULL_3","PROPERTY_FULL_4"),
			);

			$item = $res->GetNext();



        	$this->json['page']['FULL_1'] =  $item['~PROPERTY_FULL_1_VALUE']['TEXT'];
            $this->json['page']['FULL_2'] =  $item['~PROPERTY_FULL_2_VALUE']['TEXT'];
            $this->json['page']['FULL_3'] =  $item['~PROPERTY_FULL_3_VALUE']['TEXT'];
            $this->json['page']['FULL_4'] =  $item['~PROPERTY_FULL_4_VALUE']['TEXT'];

		}

	}

	?>