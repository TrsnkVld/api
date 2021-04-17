<?php

	namespace controllers;
	require_once("CommonController.php");

	ini_set('display_errors', stripos($_SERVER['HTTP_HOST'], 'spider') !== false ? 1 : 0);

	class PrerenderURLs extends CommonController {

		const ITEMS_PER_REQUEST = 20;

		public $withCache = false;

		public function post() {

			$this->json = [];
			//$this->json[] = '/catalog/product/zashch_steklo_full_screen_cover_xiaomi_redmi_4x_chern.html';
			//$this->initCatalogURLs();
			$this->initProductURLs();
		}

		protected function initCatalogURLs() {

			$IBLOCK_ID = 41;
			$date = date('Y-m-d H:i:s', strtotime('-1 day'));

			$res = \CIBlockSection::GetList(["SORT" => "ASC"],
				[
					"IBLOCK_ID"     => $IBLOCK_ID,
					'GLOBAL_ACTIVE' => 'Y',
					'ACTIVE'        => 'Y',
					'CODE'        => '_%',
/*					array(
						"LOGIC"                     => "OR",
						"PROPERTY_PRERENDERED_AT"   => false,
						"<=PROPERTY_PRERENDERED_AT" => $date,
					)*/
				], false,
				[
					'ID',
					'NAME',
					'CODE',
				]);
			while ($item = $res->Fetch()) {

				$id    = (int)$item['ID'];
				$code  = $item['CODE'];
				$xmlId = $item['XML_ID'];

				//print_r($props);
				//print_r($fields);


				//$el->Update($id, ['PROPERTY_VALUES'=>['PROPERTY_PRERENDERED_AT'=>'2018-09-25 00:00:00']]);
				//\CIBlockElement::SetPropertyValuesEx($id, $IBLOCK_ID, ['PRERENDERED_AT' => '25.09.2018 00:00:00']);

				//if( sizeof($this->json['urls'])<self::ITEMS_PER_REQUEST ) {
					//print($id . ": " . $item['PROPERTY_PRERENDERED_AT_VALUE'] . " " . $item['NAME'] . "\n");
					$this->json['urls'][] = "/catalog/" . $code . ".html";
					//\CIBlockElement::SetPropertyValuesEx($id, $IBLOCK_ID, ['PRERENDERED_AT' => '25.09.2018 01:05:00']);
					//\CIBlockElement::SetPropertyValuesEx($id, $IBLOCK_ID, ['PRERENDERED_AT' => '2018-09-25 01:05:00']);

					//$item->Update($id, ['UF_PRERENDERED_AT' => '2018-09-25 01:05:00']);
				//}
				//else break;
			}
		}

		protected function initProductURLs() {

			$IBLOCK_ID = 2;
			$date = date('Y-m-d H:i:s', strtotime('-8 hours'));

			$res = \CIBlockElement::GetList(["SORT" => "ASC"],
											[
												"IBLOCK_ID"     => $IBLOCK_ID,
												'GLOBAL_ACTIVE' => 'Y',
												'ACTIVE'        => 'Y',
												array(
													"LOGIC"                     => "OR",
													"PROPERTY_PRERENDERED_AT"   => false,
													"<=PROPERTY_PRERENDERED_AT" => $date,
												),
											], false, [
												"nTopCount" => self::ITEMS_PER_REQUEST
											],
											[
												"ID",
												"NAME",
												'CODE',
												'XML_ID',
												'PROPERTY_PRERENDERED_AT'
											]);
			while ($el = $res->GetNextElement()) {
				$props  = $el->GetProperties();
				$fields = $el->fields;

				$id    = (int)$fields['ID'];
				$code  = $fields['CODE'];
				$xmlId = $fields['XML_ID'];

				//print_r($props);
				//print_r($fields);

				//print($id . ": " . $fields['PROPERTY_PRERENDERED_AT_VALUE'] . " " . $fields['NAME'] . "\n");

				//$el->Update($id, ['PROPERTY_VALUES'=>['PROPERTY_PRERENDERED_AT'=>'2018-09-25 00:00:00']]);
				//\CIBlockElement::SetPropertyValuesEx($id, $IBLOCK_ID, ['PRERENDERED_AT' => '25.09.2018 00:00:00']);


				if( sizeof($this->json)<self::ITEMS_PER_REQUEST ) {
					$this->json[] = "/catalog/product/" . $code . ".html";
					$now = date('Y-m-d H:i:s');
					\CIBlockElement::SetPropertyValuesEx($id, $IBLOCK_ID, ['PRERENDERED_AT' => $now]);
				}
				else break;
			}

		}
	}