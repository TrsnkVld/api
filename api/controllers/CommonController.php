<?php

	namespace controllers;

	class CommonController {
		public $withCache = TRUE;

		public static $APPLICATION;
		public static $USER;
		public static $lastBitrixComponentResult;
		public $json = [];

		public function __construct() {
		}

		public function init($APPLICATION, $USER) {
			self::$APPLICATION = $APPLICATION;
			self::$USER = $USER;
		}

		public function get() {
			\CModule::IncludeModule('iblock');
			//$this->json['common']['phone'] = '+7 800 333-40-05';
			//$this->json['common']['debug']['get'] = $_GET;
			//$this->json['common']['debug']['post'] = $_POST;
			$this->initMetas();
			$this->initTypes();
			$this->initTexts();
		}

		protected function initMetas() {
			$this->json['page']['title'] = "";
			$this->json['page']['description'] = "";
			$this->json['page']['keywords'] = "";
		}

		/**
		 * Инициализируем типы картона.
		 */
		protected function initTypes() {
			// на нужен справочник типов ламинации
			$laminationTypes = [];
			$res = \CIBlockElement::GetList(
				[
					'SORT' => 'ASC'
				],
				[
					'=ACTIVE'    => 'Y',
					'IBLOCK_CODE' => \Config::IBLOCK_CODE_LAMINATION_TYPES,
				],
				FALSE,
				FALSE,
				);
			while ($row = $res->Fetch()) {
				$laminationTypes[$row['ID']] = $row;
			}
			//d($laminationTypes);

			$res = \CIBlockElement::GetList(
				[
					'SORT' => 'ASC'
				],
				[
					'=ACTIVE'    => 'Y',
					//'IBLOCK_ID' => \Config::IBLOCK_ID_CARDBOARD_TYPES,
					'IBLOCK_CODE' => \Config::IBLOCK_CODE_CARDBOARD_TYPES,
				],
				FALSE,
				FALSE,
				[
					'ID',
					'NAME',
					'PREVIEW_TEXT',
					'PREVIEW_PICTURE',
					'DETAIL_TEXT',
					'PROPERTY_PRICE_MIN',
					'PROPERTY_PORTFOLIO_TEXT',
					'PROPERTY_PORTFOLIO_PHOTOS',
					'PROPERTY_TEXTURE_TYPE',
					'PROPERTY_MATERIAL_SIDES',
                    'PROPERTY_LAMINATION_TYPES',
					'PROPERTY_DEFAULT_PRINT',
					'PROPERTY_DEFAULT_LOGO',
					'PROPERTY_DEFAULT_MATERIAL_OUTSIDE',
					'PROPERTY_DEFAULT_MATERIAL_INSIDE',
					'PROPERTY_PRICE_FILE',
					'PROPERTY_PRICE_FILE_TYPE',
                    'PROPERTY_SORT_FOR_PORTFOLIO_PAGE',
				]
			);

			$items = [];
			while ($row = $res->Fetch()) {
				$image = \CFile::GetPath($row['PREVIEW_PICTURE']);
				if ( $image ) $row['PREVIEW_PICTURE'] = $image;
				$image = \CFile::GetPath($row['PROPERTY_DEFAULT_LOGO_VALUE']);
				if ( $image ) $row['PROPERTY_DEFAULT_LOGO_VALUE'] = $image;

				$portfolioImages = [];
				if ($row['PROPERTY_PORTFOLIO_PHOTOS_VALUE']) {
					foreach ($row['PROPERTY_PORTFOLIO_PHOTOS_VALUE'] as $portfolioImageId) {
						$portfolioImages[] = self::optimizeImage($portfolioImageId, \Config::PORTFOLIO_CATEGORY_IMG_WIDTH, \Config::PORTFOLIO_CATEGORY_IMG_HEIGHT);
//						$portfolioImages[] = \CFile::GetPath($portfolioImageId);
					}
				}
				$row['PORTFOLIO_PHOTOS'] = $portfolioImages;

				// собрать коды типов ламинации
				if ( sizeof($row['PROPERTY_LAMINATION_TYPES_VALUE']) ) {
					$laminationTypeCodes = [];
					foreach ( $row['PROPERTY_LAMINATION_TYPES_VALUE'] as $ltId ) {
						$laminationTypeCodes[] = $laminationTypes[$ltId]['CODE'];
					}
					$row['LAMINATION_TYPES'] = $laminationTypeCodes;
					unset($row['PROPERTY_LAMINATION_TYPES_VALUE']);
				}

				// TODO: перенести очистку в preprocessJson (api.php)
				unset($row['PROPERTY_PORTFOLIO_PHOTOS_DESCRIPTION']);

				/*$row['PROPERTY_WITH_TEXTURES'] = ($row['PROPERTY_WITH_TEXTURES_VALUE'] == 'Да' ? true : false);
				$row['PROPERTY_WITH_CRAFT'] = ($row['PROPERTY_WITH_CRAFT_VALUE'] == 'Да' ? true : false);
				$row['PROPERTY_WITH_INSIDE'] = ($row['PROPERTY_WITH_INSIDE_VALUE'] == 'Да' ? true : false);*/

				// убираем лишние свойства:
				/*unset($row['PROPERTY_PRICE_MIN_VALUE_ID']);
				unset($row['PROPERTY_PORTFOLIO_PHOTOS_VALUE']);
				unset($row['PROPERTY_PORTFOLIO_PHOTOS_DESCRIPTION']);
				unset($row['PROPERTY_WITH_TEXTURES_VALUE']);
				unset($row['PROPERTY_WITH_TEXTURES_ENUM_ID']);
				unset($row['PROPERTY_WITH_TEXTURES_VALUE_ID']);
				unset($row['PROPERTY_WITH_CRAFT_VALUE']);
				unset($row['PROPERTY_WITH_CRAFT_ENUM_ID']);
				unset($row['PROPERTY_WITH_CRAFT_VALUE_ID']);
				unset($row['PROPERTY_WITH_INSIDE_VALUE']);
				unset($row['PROPERTY_WITH_INSIDE_ENUM_ID']);
				unset($row['PROPERTY_WITH_INSIDE_VALUE_ID']);*/

				$items[] = $row;
			}
			//d($items);

			$this->json['common']['types'] = $items;
		}

		protected function initTexts() {

            $res = \CIBlockElement::GetList(
                [
                    'SORT' => 'ASC'
                ],
                [
                    'ACTIVE'    => 'Y',
                    'IBLOCK_CODE' => \Config::IBLOCK_CODE_TEXTS,
                ],
                FALSE,
                FALSE,
                [
                    'ID',
                    'IBLOCK_ID',
                    'NAME',
                    'DETAIL_TEXT',
                    'CODE',
                    /*'PROPERTY_PRICE_MIN',
                    'PROPERTY_PORTFOLIO_PHOTOS',
                    'PROPERTY_DESCRIPTION',*/
                ]
            );

            $items = [];
            while ($row = $res->Fetch()) {
                if (!$row['CODE']) continue;
                $key = $row['CODE'];

                unset($row['DETAIL_TEXT_TYPE']);
                $items[$key] = $row;
            }

            $this->json['common']['texts'] = $items;
        }

		protected static function serverURI() {
			$isHttps = preg_match("/^https:.+/i", $_SERVER['HTTP_REFERER']) || $_SERVER['HTTPS'];
			return "http" . ($isHttps ? "s" : "") . "://" . $_SERVER['HTTP_HOST'];
			//return "http://" . $_SERVER['HTTP_HOST'];
		}

		protected static function proxy($url, $method, $params = NULL, $customHeaders = NULL) {
			$json = file_get_contents('php://input');
			$post = json_decode($json, 1);

			$cookies = array();
			foreach ($_COOKIE as $key => $value) {
				if ($key != 'Array') {
					$cookies[] = $key . '=' . $value;
				}
			}
			if ($params) $data_string = http_build_query($params);
			else $data_string = '';

			$headers = [
				//            'Bx-ajax: true',
				'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
				'Upgrade-Insecure-Requests: 1',
				'Content-Length: ' . strlen($data_string),
			];
			if ($customHeaders) {
				foreach ($customHeaders as $key => $value) {
					$headers[] = $key . ": " . $value;
				}
			}

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			if ($method == "POST") curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    // важно для https запроса!
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_COOKIE, implode(';', $cookies));
			session_write_close();

			try {
				$response = curl_exec($ch);
			} catch (Exception $e) {
				die($e);
			}
			curl_close($ch);

			$m = [];
			preg_match("/(.+?)\r\n\r\n(.+)/s", $response, $m);

			return [$m[1], $m[2]];
		}

		protected static function postParams() {
			$json = file_get_contents('php://input');
			if ($json) $params = json_decode($json, 1);
			else $params = $_POST;
			return $params;
		}

		protected static function fixBitrixJSON($json) {
			$regex = <<<'REGEX'
~
    "[^"\\]*(?:\\.|[^"\\]*)*"
    (*SKIP)(*F)
  | '([^'\\]*(?:\\.|[^'\\]*)*)'
~x
REGEX;

			return preg_replace_callback($regex, function ($matches) {
				return '"' . preg_replace('~\\\\.(*SKIP)(*F)|"~', '\\"', $matches[1]) . '"';
			}, $json);
		}

		public static function runBitrixComponent($name, $template, $params) {
			self::$APPLICATION->IncludeComponent($name, $template, $params, false);
			return self::$lastBitrixComponentResult;
		}

		protected static function mapArray($items, $map) {
			$newItems = [];
			foreach ($items as $index => $item) {
				foreach ($map as $oldKey => $newKey) {
					$newItems[$index][$newKey] = $item[$oldKey];
				}
			}
			return $newItems;
		}

        /**
         * Функия возвращает название раздела.
         * @param $sectionCode - код раздела
         * @param $blockCode - код инфоблока
         * @return string | null
         */
        protected  static function sectionName($blockCode, $sectionCode) {
            $rsSections = \CIBlockSection::GetList(array(),array('IBLOCK_CODE' => $blockCode, '=CODE' => $sectionCode), false,  Array("UF_STUB_SECTION"));
            if ($arSection = $rsSections->Fetch())
            {
                return $arSection['NAME'];
            }

            return null;
        }

        /**
         * Оптимазация картинки под заданный размер
         * @see https://dev.1c-bitrix.ru/api_help/main/reference/cfile/resizeimageget.php
         *
         * @param string $imageId - id картинки
         * @param string $width -
         * @param string $height -
         * @param string $resizeType
         * @param number $quality
         * @return string - src to image
         */
        protected static function optimizeImage($imageId, $width, $height, $resizeType = 'BX_RESIZE_IMAGE_PROPORTIONAL', $quality = \Config::DEFAULT_PHOTO_QUALITY) {
            // оптимизируем картиники на лету
            $optimizeImage = \CFile::ResizeImageGet($imageId, Array("width" => $width, "height" => $height), $resizeType, false, false, false, $quality);
            return $optimizeImage['src'];
        }
	}

?>
