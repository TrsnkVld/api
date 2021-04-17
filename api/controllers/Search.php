<?php

	namespace controllers;
	require_once "CommonController.php";
	require_once "../api/tools/Product.php";

	class Search extends CommonController {

		//public $withCache = false;

		public function get() {
			parent::get();

			$params = $_GET;
			//d($params);

			/*$IBLOCK_TYPE = \COption::GetOptionString("sotbit.mistershop", "IBLOCK_TYPE", "");
			$IBLOCK_ID = \COption::GetOptionString("sotbit.mistershop", "IBLOCK_ID", "");
			$res = self::runBitrixComponent(
				"bitrix:search.page",
				"API",
				Array(
					"RESTART" => "N",
					"NO_WORD_LOGIC" => "Y",
					"USE_LANGUAGE_GUESS" => "Y",
					"CHECK_DATES" => "Y",
					"arrFILTER" => array("iblock_".$IBLOCK_TYPE),
					"arrFILTER_iblock_".$IBLOCK_TYPE => array($IBLOCK_ID),
					"USE_TITLE_RANK" => "N",
					"DEFAULT_SORT" => "rank",
					"FILTER_NAME" => "",
					"SHOW_WHERE" => "N",
					"arrWHERE" => array(),
					"SHOW_WHEN" => "N",
					"PAGE_RESULT_COUNT" => 50,
					"DISPLAY_TOP_PAGER" => "N",
					"DISPLAY_BOTTOM_PAGER" => "N",
					"PAGER_TITLE" => GetMessage("SEARCH_TITLE"),
					"PAGER_SHOW_ALWAYS" => "N",
					"PAGER_TEMPLATE" => "N",

				));
			d($res);
			$items = [];
			foreach ( $res['SEARCH'] as $item ) {
				d($item['ID']);
				$items[] = [
					"name" => $item['TITLE'],
					"text" => $item['BODY'],
					"product" => \tools\Product::fetchByIdOrCode($item['ID'])
				];
			}
			d($items);
			$this->json['page']['Search'] = $items;*/

			$s = trim($params['search']);
			if (!$s) return;

			// в поиске можно указать несколько позиций через запятую:
			$parts = explode(",", $s);

			// поиск возможен по имени, внешнему коду и артикулу:
			$names = [];
			$codes = [];
			$skus = [];
			foreach ($parts as $part) {
				$part = trim($part);
				if (preg_match("/^\d{5}_?$/", $part)) {
					// это внешний код
					$codes[] = $part;
				} else if (preg_match("/^FT-[.A-Z0-9-]+$/", $part)) {
					// это артикул
					$skus[] = $part;
					/*$codesRaw = explode(",", $part);
					foreach ( $codesRaw as $code ) {
					}*/
				} else {
					// это поиск по имени:
					$names[] = "%{$part}%";
				}
			}

			// формируем поисковый фильтр:
			$arFilter = [
				"IBLOCK_ID" => \Config::ID_IBLOCK_CATALOG,
				"GLOBAL_ACTIVE" => "Y",
				"ACTIVE" => "Y",
				"SECTION_ACTIVE" => "Y",
				"SECTION_GLOBAL_ACTIVE" => "Y",
				//"INCLUDE_SUBSECTIONS" => "Y",
				"NAME" => "%",
			];
			$filterOr = ["LOGIC" => "OR"];
			//if (sizeof($codes)) $filterOr["XML_ID"] = $codes;
			if (sizeof($codes)) $filterOr["PROPERTY_KOD_TOVARA_NA_SAYTE"] = $codes;
			if (sizeof($skus)) $filterOr["PROPERTY_CML2_ARTICLE"] = $skus;
			if (sizeof($names)) $filterOr["NAME"] = $names;//"%{$s}%";
			$arFilter[] = $filterOr;
			//d($arFilter);

			// вытягиваемые поля:
			$arSelect = [
				"ID",
				"NAME",
			];

			// запрос:
			$res = \CIBlockElement::GetList(["CATALOG_AVAILABLE" => "DESC","SORT" => "ASC"], $arFilter, FALSE, ['nTopCount' => \Config::SEARCH_LIMIT], $arSelect);
			$products = [];
			while ($el = $res->GetNextElement()) {
				$props = $el->GetProperties();
				$fields = $el->fields;
				//d($fields);

				// используем ключ для уникализации, т.к. иногда поиск возвращает один и тот же товар, если он находится в разных категориях:
				if ( !$products[$fields['ID']] ) $products[$fields['ID']] = \tools\Product::fetchByIdOrCode($fields['ID']);
			}
			//d($products);

			$this->json = array_values($products);
		}
	}