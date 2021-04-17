<?php

	namespace controllers;

	require $_SERVER['DOCUMENT_ROOT'].'/../api/vendor/autoload.php';
	use PhpOffice\PhpSpreadsheet\IOFactory;
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

	require_once "CommonController.php";

	/**
	 * Class OrderType
	 * Отдает все данные по типу картона и его моделям.
	 * @package controllers
	 */
	class OrderType extends CommonController {

		public function get() {
			parent::get();
			$params = $_GET;
			//d($params);

			// валидация
			$params['typeId'] = intval($params['typeId']);

			// сам тип уже есть common
			$type = NULL;
			foreach ( $this->json['common']['types'] as $t ) {
				if ( $t['ID'] == $params['typeId']) {
					$type = $t;
					break;
				}
			}
			if ( !$type ) throw new \Exception("Данный тип картона не найден");

//			d($type);

			$this->initModels($params);

			if ( $type['PROPERTY_PRICE_FILE_TYPE_ENUM_ID'] == \Config::ID_PRICE_FILE_TYPE_TEXTURES ) $this->initPricesWithTextures($type, $params);
			else $this->initPrices($type, $params);
			$this->initTails();
			$this->initFillings();

			//d($type);

			// текстуры материалов
			if ( $type['PROPERTY_TEXTURE_TYPE_ENUM_ID'] == \Config::ID_TEXTURE_TYPE_TEXTURE ) $this->initTextures();
			else if ( $type['PROPERTY_TEXTURE_TYPE_ENUM_ID'] == \Config::ID_TEXTURE_TYPE_CRAFT ) $this->initCraftTextures();
			else if ( $type['PROPERTY_TEXTURE_TYPE_ENUM_ID'] == \Config::ID_TEXTURE_TYPE_FULL ) $this->initFullTextures();
		}

		/**
		 * Берем модели, применимые в данном типа картона.
		 */
		protected function initModels($params) {
			$res = \CIBlockElement::GetList(
				[
					'SORT' => 'ASC'
				],
				[
					'IBLOCK_CODE' => \Config::IBLOCK_CODE_MODELS,
					'=ACTIVE'    => 'Y',
					'PROPERTY_CARDBOARD_TYPES' => [$params['typeId']],
				],
				FALSE,
				FALSE,
				[
					'ID',
					//'IBLOCK_ID',
					'NAME',
					'PREVIEW_TEXT',
					'PREVIEW_PICTURE',
					'DETAIL_TEXT',
					'DETAIL_PICTURE',
					'PROPERTY_MESH_FILES',
                    'PROPERTY_TEXTURE_FILES',
                    'PROPERTY_PORTFOLIO_PHOTOS',
                    'PROPERTY_DESIGN_FILE',
                    'PROPERTY_DEFAULT_SIZE',
                    'PROPERTY_IS_LWH',
                    'PROPERTY_COVER_ROTATION_JSON',
                    'PROPERTY_FIXED_COST_JSON',
				]
			);
			$items = [];
			while ($row = $res->Fetch()) {
//				r($row);
				//$row['PREVIEW_PICTURE'] = \CFile::GetFileArray($row['PREVIEW_PICTURE']);

				// картинки-рендеры
				$file = \CFile::GetPath($row['PREVIEW_PICTURE']);
				if ( $file ) $row['PREVIEW_PICTURE'] = $file;
				else unset($row['PREVIEW_PICTURE']);
				$file = \CFile::GetPath($row['DETAIL_PICTURE']);
				if ( $file ) $row['DETAIL_PICTURE'] = $file;
				else unset($row['DETAIL_PICTURE']);

				// файлы моделей
				$row['MESH_FILES'] = [];
				foreach ( $row['PROPERTY_MESH_FILES_VALUE'] as $meshFile ) {
					$file = \CFile::GetPath($meshFile);
					if ( $file ) $row['MESH_FILES'][] = $file;
				}

				// фотки портофлио
				$row['PORTFOLIO_PHOTOS'] = [];
				foreach ( $row['PROPERTY_PORTFOLIO_PHOTOS_VALUE'] as $portfolio ) {
					$file = parent::optimizeImage($portfolio, \Config::PORTFOLIO_MODEL_IMG_WIDTH, \Config::PORTFOLIO_MODEL_IMG_HEIGHT);
					if ( $file ) $row['PORTFOLIO_PHOTOS'][] = $file;
				}

				// макет дизайна
				if ( $row["PROPERTY_DESIGN_FILE_VALUE"] ) {
					$file = \CFile::GetPath($row["PROPERTY_DESIGN_FILE_VALUE"]);
					if ( $file ) $row['DESIGN_FILE'] = $file;
				}

				// файлы текстур для ламинации (оверрайдит то, что инициализируется в initFullTextures())
				$row['TEXTURE_FILES'] = [];
				foreach ( $row['PROPERTY_TEXTURE_FILES_VALUE'] as $i => $textureFile ) {
					$file = \CFile::GetFileArray($textureFile);
					$name = $row['PROPERTY_TEXTURE_FILES_DESCRIPTION'][$i];
					if ( !$name ) $name = preg_replace("/(^.+)\..+$/", "$1", $file['ORIGINAL_NAME']);
					$row['TEXTURE_FILES'][] = ["NAME"=>$name, "URL"=>$file['SRC']];
				}

                // русское 'х' в английское 'x':
				$row['PROPERTY_DEFAULT_SIZE_VALUE'] = str_replace("х", "x", mb_strtolower($row['PROPERTY_DEFAULT_SIZE_VALUE']));

                // TODO: перенести очистку в preprocessJson (api.php)
				unset($row['PROPERTY_MESH_FILES_DESCRIPTION']);
				unset($row['PROPERTY_PORTFOLIO_PHOTOS_DESCRIPTION']);
				unset($row['PROPERTY_TEXTURE_FILES_DESCRIPTION']);
				/*
				unset($row['PROPERTY_MESH_FILES_PROPERTY_VALUE_ID']);
				unset($row['PROPERTY_MESH_FILES_VALUE']);
                unset($row['PROPERTY_PORTFOLIO_PHOTOS_PROPERTY_VALUE_ID']);
                unset($row['PROPERTY_PORTFOLIO_PHOTOS_VALUE']);*/

				//unset($row['PROPERTY_DEFAULT_SIZE_VALUE']);

				// лишние поля убираем
				unset($row['PREVIEW_TEXT_TYPE']);

				// упрощаем передачу JSON свойств
				$row['PROPERTY_COVER_ROTATION_JSON_VALUE'] = $row['PROPERTY_COVER_ROTATION_JSON_VALUE']['TEXT'];
				$row['PROPERTY_FIXED_COST_JSON_VALUE'] = $row['PROPERTY_FIXED_COST_JSON_VALUE']['TEXT'];

				$items[] = $row;
			}
			//d($items);
			$this->json['page']['models'] = $items;
		}

		/**
		 * Берем все текстуры бумаги фактурного картона.
		 * Важно! Взаимоисключающая с initFullTextures() и initCraftTextures();
		 */
		protected function initTextures() {
			$res = \CIBlockSection::GetList(
				[
					'SORT' => 'ASC'
				],
				[
					'=ACTIVE'    => 'Y',
					'=GLOBAL_ACTIVE'    => 'Y',
					'IBLOCK_ID' => \Config::IBLOCK_ID_TEXTURES,	// palmface: по IBLOCK_CODE не вытягивает UF_*
					//'IBLOCK_CODE' => \Config::IBLOCK_CODE_TEXTURES,
				],
				false,
				[
					'ID',
					'NAME',
					'DESCRIPTION',
					'UF_SHORT',
				]
			);
			$sections = [];
			while ($row = $res->Fetch()) {
				//d($row);
				unset($row['SORT']);
				unset($row['DESCRIPTION_TYPE']);
				$sections[] = $row;
			}

			$res = \CIBlockElement::GetList(
				[
					'SORT' => 'ASC'
				],
				[
					'=ACTIVE'    => 'Y',
					'=SECTION_GLOBAL_ACTIVE'    => 'Y',
					'IBLOCK_CODE' => \Config::IBLOCK_CODE_TEXTURES,
				],
				FALSE,
				FALSE,
				[
					'ID',
					'NAME',
					'PREVIEW_PICTURE',
					'IBLOCK_SECTION_ID',
				]
			);

			$items = [];
			while ($row = $res->Fetch()) {
				$image = \CFile::GetPath($row['PREVIEW_PICTURE']);
				if ( $image ) $row['PREVIEW_PICTURE'] = $image;

				$items[] = $row;
			}

			$textures = [];
			foreach ( $sections as $section ) {
				$section['MAPS'] = [];
				foreach ( $items as $item ) {
					if ( $item['IBLOCK_SECTION_ID'] != $section['ID']) continue;

					unset($item['SORT']);
					unset($item['IBLOCK_SECTION_ID']);
					$section['MAPS'][] = $item;
				}

				if ( !sizeof($section['MAPS']) ) continue;

				$textures[] = $section;
			}

			$this->json['page']['textures'] = $textures;
		}

		/**
		 * Берем все текстуры крафта.
		 * Важно! Взаимоисключающая с initTextures() и initFullTextures();
		 */
		protected function initCraftTextures() {
			$res = \CIBlockElement::GetList(
				[
					'SORT' => 'ASC'
				],
				[
					'=ACTIVE'    => 'Y',
					'IBLOCK_CODE' => \Config::IBLOCK_CODE_CRAFT_TEXTURES,
				],
				FALSE,
				FALSE,
				[
					'ID',
					'NAME',
					'PREVIEW_PICTURE',
				]
			);

			$textures = [];
			while ($row = $res->Fetch()) {
				$image = \CFile::GetPath($row['PREVIEW_PICTURE']);
				if ( $image ) $row['PREVIEW_PICTURE'] = $image;

				unset($row['SORT']);

				$textures[] = $row;
			}

			$this->json['page']['textures'] = $textures;
		}

		/**
		 * Берем все текстуры ламинации (полной заливки).
		 * Важно! Взаимоисключающая с initTextures() и initCraftTextures();
		 */
		protected function initFullTextures() {
			$res = \CIBlockElement::GetList(
				[
					'SORT' => 'ASC'
				],
				[
					'=ACTIVE'    => 'Y',
					'IBLOCK_CODE' => \Config::IBLOCK_CODE_FULL_TEXTURES,
				],
				FALSE,
				FALSE,
				[
					'ID',
					'NAME',
					'PREVIEW_PICTURE',
					'DETAIL_PICTURE',
				]
			);

			$textures = [];
			while ($row = $res->Fetch()) {
				$previewImageId = $row['PREVIEW_PICTURE'];
				$image = \CFile::GetPath($previewImageId);
				if ( $image ) $row['PREVIEW_PICTURE'] = self::optimizeImage($previewImageId, \Config::FULL_TEXTURE_PREVIEW_WIDTH, \Config::FULL_TEXTURE_PREVIEW_HEIGHT);
				else $row['PREVIEW_PICTURE'] = NULL;

				if ( $row['DETAIL_PICTURE'] ) {
					// есть детальная картинка
					$image = \CFile::GetPath($row['DETAIL_PICTURE']);
					if ( $image ) {
						if ( !$row['PREVIEW_PICTURE'] ) {
							// нет анонса, делаем из детальной
							$row['PREVIEW_PICTURE'] = self::optimizeImage($row['DETAIL_PICTURE'], \Config::FULL_TEXTURE_PREVIEW_WIDTH, \Config::FULL_TEXTURE_PREVIEW_HEIGHT);
						}
						$row['DETAIL_PICTURE'] = $image;
					}
					else $row['DETAIL_PICTURE'] = NULL;
				}
				else if ( $row['PREVIEW_PICTURE'] ) {
					// делаем детальную картинку из анонса (без ресайза)
					$row['DETAIL_PICTURE'] = \CFile::GetPath($previewImageId);
				}


				// посмотреть, если в моделях есть оверрадйы для общих ламинирующих текстур
				/*foreach ( $this->json['page']['models'] as $model ) {
					if ( !sizeof($model['TEXTURE_FILES']) ) continue;

					// есть собственные текстуры, заменить
					d($model['TEXTURE_FILES']);
				}*/

				unset($row['SORT']);

				$textures[] = $row;
			}

			$this->json['page']['textures'] = $textures;
		}

		/**
		 * Берем все текстуры наполнений.
		 */
		protected function initFillings() {
			$res = \CIBlockSection::GetList(
				[
					'SORT' => 'ASC'
				],
				[
					'=ACTIVE'    => 'Y',
					'=GLOBAL_ACTIVE'    => 'Y',
					// без этого не получить UF_* :()
					'IBLOCK_ID' => \Config::IBLOCK_ID_FILLINGS,
					//'IBLOCK_CODE' => \Config::IBLOCK_CODE_FILLINGS,
				],
				false,
				[
					'ID',
					'NAME',
					'DESCRIPTION',
					'UF_PRICE',
					'UF_SHORT',
				]
			);
			$sections = [];
			while ($row = $res->Fetch()) {
				unset($row['SORT']);
				unset($row['DESCRIPTION_TYPE']);
				$sections[] = $row;
			}

			$res = \CIBlockElement::GetList(
				[
					'SORT' => 'ASC'
				],
				[
					'=ACTIVE'    => 'Y',
					'=SECTION_GLOBAL_ACTIVE'    => 'Y',
					'IBLOCK_CODE' => \Config::IBLOCK_CODE_FILLINGS,
				],
				FALSE,
				FALSE,
				[
					'ID',
					'NAME',
					'PREVIEW_TEXT',
					'PREVIEW_PICTURE',
					'PROPERTY_PHOTOS',
					'IBLOCK_SECTION_ID',
				]
			);

			$items = [];
			while ($row = $res->Fetch()) {
				$image = \CFile::GetPath($row['PREVIEW_PICTURE']);
				if ( $image ) $row['PREVIEW_PICTURE'] = $image;

				if ($row['PROPERTY_PHOTOS_VALUE']) {
					$row['PHOTOS'] = [];
					foreach ($row['PROPERTY_PHOTOS_VALUE'] as $imageId ) {
						$row['PHOTOS'][] = \CFile::GetPath($imageId);
					}
				}

				$items[] = $row;
			}

			$textures = [];
			foreach ( $sections as $section ) {
				$section['MAPS'] = [];
				foreach ( $items as $item ) {
					if ( $item['IBLOCK_SECTION_ID'] != $section['ID']) continue;

					unset($item['SORT']);
					unset($item['IBLOCK_SECTION_ID']);
					$section['MAPS'][] = $item;
				}

				if ( !sizeof($section['MAPS']) ) continue;

				$textures[] = $section;
			}

			$this->json['page']['fillings'] = $textures;
		}

		/**
		 * Берем все ленты.
		 */
		protected function initTails() {
			$res = \CIBlockElement::GetList(
				[
					'SORT' => 'ASC'
				],
				[
					'=ACTIVE'    => 'Y',
					'IBLOCK_CODE' => \Config::IBLOCK_CODE_TAILS,
				],
				FALSE,
				FALSE,
				[
					'ID',
					'NAME',
					'PREVIEW_PICTURE',
				]
			);

			$textures = [];
			while ($row = $res->Fetch()) {
				$image = \CFile::GetPath($row['PREVIEW_PICTURE']);
				if ( $image ) $row['PREVIEW_PICTURE'] = $image;

				unset($row['SORT']);

				$textures[] = $row;
			}

			$this->json['page']['tails'] = $textures;
		}

		/**
		 * Формируем цены для данного типа картона.
		 * @param $type
		 * @param $params
		 * @throws \PhpOffice\PhpSpreadsheet\Exception
		 * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
		 */
		protected function initPrices($type, $params) {
			// файлы с ценами хранятся в категориях


			// есть ли у категории файл с ценами?
			if ( !$type['PROPERTY_PRICE_FILE_VALUE'] ) return;
			$priceFile = \CFile::GetPath($type['PROPERTY_PRICE_FILE_VALUE']);
			if ( !$priceFile ) return;

			// читаем цены и разносим по моделям
			$spreadsheet = IOFactory::load($_SERVER['DOCUMENT_ROOT'].$priceFile);
			$sheets = $spreadsheet->getAllSheets();
			foreach ( $this->json['page']['models'] as $modelIndex => $model ) {
				$modelName = mb_strtoupper(trim($model['NAME']));

				// перебираем листы в поисках модели
				foreach ( $sheets as $sheet ) {
					$title = mb_strtoupper(trim($sheet->getTitle()));
					//r($title.":".$modelName);
					if ( $modelName != $title ) continue;

					// это лист с данными модели

					// начинаем с чтения тиража (8 шт.)
					$row = 2;
					$amounts = [];
					$col = ord('D');
					for ( $i=0; $i<8; $i++) {
						$amounts[] = intval($sheet->getCell(self::chr($col+$i) . $row)->getCalculatedValue());
					}

					// определяем кол-во колонок под параметры Материалы, Ламинация, Нанесение (в зависимости от типа текстуры):
					$textures = [];
					$laminations = [];
					$prints = [];
					if ( $type['PROPERTY_TEXTURE_TYPE_ENUM_ID'] == \Config::ID_TEXTURE_TYPE_TEXTURE ) {
						// текстуры 11 колонок TODO: как-то нужно согласовать это с тем, что есть в БД?
						$col = ord('L');
						for ( $i=0; $i<11; $i++) {
							$textures[] = trim($sheet->getCell(self::chr($col+$i) . $row)->getCalculatedValue());
						}

						// типы нанесения 8 колонок
						$prints = [
							["print" => \Config::SIDE_PRINT_SILK, "what" => "image", "where" => "outside"],
							["print" => \Config::SIDE_PRINT_SILK, "what" => "text", "where" => "outside"],
							["print" => \Config::SIDE_PRINT_EMBOSSING, "what" => "image", "where" => "outside"],
							["print" => \Config::SIDE_PRINT_EMBOSSING, "what" => "text", "where" => "outside"],
							["print" => \Config::SIDE_PRINT_SILK, "what" => "image", "where" => "inside"],
							["print" => \Config::SIDE_PRINT_SILK, "what" => "text", "where" => "inside"],
							["print" => \Config::SIDE_PRINT_EMBOSSING, "what" => "image", "where" => "inside"],
							["print" => \Config::SIDE_PRINT_EMBOSSING, "what" => "text", "where" => "inside"],
						];
					}
					else if ( $type['PROPERTY_TEXTURE_TYPE_ENUM_ID'] == \Config::ID_TEXTURE_TYPE_FULL ) {
						// ламинация 1 колонка
						$laminations = [
							\Config::LAMINATION_SOFT_TOUCH,
						];
						// типы нанесения 2 колонки
						$prints = [
							["print" => \Config::SIDE_PRINT_EMBOSSING, "what" => "image", "where" => "outside"],
							["print" => \Config::SIDE_PRINT_EMBOSSING, "what" => "text", "where" => "outside"],
						];
					}

					// теперь идем по строчкам с ценами
					$model['prices'] = [];
					$row = 3;
					do {
						$p = [];
						$col = ord('A');

						// sizes - 3 cols
						$p['width'] = intval($sheet->getCell(self::chr($col+0) . $row)->getCalculatedValue());
						$p['length'] = intval($sheet->getCell(self::chr($col+1). $row)->getCalculatedValue());
						$p['height'] = intval($sheet->getCell(self::chr($col+2). $row)->getCalculatedValue());
						if ( !$p['width'] || !$p['length'] || !$p['height'] ) break;
						$col+=3;

						// amounts - as many cols as we have in $amounts
						$p['amounts'] = [];
						foreach ( $amounts as $i => $value ) {
							//d($value.":".$sheet->getCell(self::chr($col+$i) . $row)->getCalculatedValue());
							$p['amounts'][$value] = floatval($sheet->getCell(self::chr($col+$i) . $row)->getCalculatedValue());
						}
						$col+=sizeof($amounts);

						// textures - as many cols as we have in $textures
						if ( sizeof($textures) ) {
							$p['textures'] = [];
							foreach ($textures as $i => $name) {
								$p['textures'][$name] = floatval($sheet->getCell(self::chr($col + $i) . $row)->getCalculatedValue());
							}
							$col += sizeof($textures);
						}

						// tail
						$p['tail'] = floatval($sheet->getCell(self::chr($col++) . $row)->getCalculatedValue());

						// виды ламинации
						if ( sizeof($laminations) ) {
							$p['lamination'] = [];
							foreach ($laminations as $lam) {
								$p['lamination'][$lam] = floatval($sheet->getCell(self::chr($col++) . $row)->getCalculatedValue());
							}
						}

						// виды нанесения
						foreach ( $prints as $print ) {
							$p[$print['what']][$print['print']][$print['where']] = floatval($sheet->getCell(self::chr($col++) . $row)->getCalculatedValue());
						}

						// Прочие процедуры
						$p['misc'] = floatval($sheet->getCell(self::chr($col++) . $row)->getCalculatedValue());

						$model['prices'][] = $p;
						$row++;
					} while ( $row < 300 );	// на всякий случай ограничиваем

					$this->json['page']['models'][$modelIndex] = $model;
				}
			}
		}

		/**
		 * Формируем цены для данного типа картона из файла с текстурами в строчках.
		 * @param $type
		 * @param $params
		 * @throws \PhpOffice\PhpSpreadsheet\Exception
		 * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
		 */
		protected function initPricesWithTextures($type, $params) {
			// файлы с ценами хранятся в категориях

			// есть ли у категории файл с ценами?
			if ( !$type['PROPERTY_PRICE_FILE_VALUE'] ) return;
			$priceFile = \CFile::GetPath($type['PROPERTY_PRICE_FILE_VALUE']);
			if ( !$priceFile ) return;

			// читаем цены и разносим по моделям
			$spreadsheet = IOFactory::load($_SERVER['DOCUMENT_ROOT'].$priceFile);
			$sheets = $spreadsheet->getAllSheets();
			foreach ( $this->json['page']['models'] as $modelIndex => $model ) {
				$modelName = mb_strtoupper(trim($model['NAME']));

				// перебираем листы в поисках модели
				foreach ( $sheets as $sheet ) {
					$title = mb_strtoupper(trim($sheet->getTitle()));
					//r("Sheet ".$title."\n");
					if ( $modelName != $title ) continue;

					// это лист с данными модели

					// начинаем с чтения тиража (8 шт.)
					$row = 2;
					$amounts = [];
					$col = ord('E');
					for ( $i=0; $i<8; $i++) {
						$amounts[] = intval($sheet->getCell(self::chr($col+$i) . $row)->getCalculatedValue());
					}

					// типы нанесения
					$prints = [
						["print" => \Config::SIDE_PRINT_SILK, "what" => "image", "where" => "outside"],
						["print" => \Config::SIDE_PRINT_SILK, "what" => "text", "where" => "outside"],
						["print" => \Config::SIDE_PRINT_EMBOSSING, "what" => "image", "where" => "outside"],
						["print" => \Config::SIDE_PRINT_EMBOSSING, "what" => "text", "where" => "outside"],
					];

					// теперь идем по строчкам
					$model['prices'] = [];
					$row = 3;
					do {
						$p = [];
						$col = ord('A');

						// читаем текстуру
						$p['texture'] = mb_strtoupper(trim($sheet->getCell(self::chr($col++) . $row)->getCalculatedValue()));

						// sizes - 3 cols
						$p['width'] = intval($sheet->getCell(self::chr($col+0) . $row)->getCalculatedValue());
						$p['length'] = intval($sheet->getCell(self::chr($col+1). $row)->getCalculatedValue());
						$p['height'] = intval($sheet->getCell(self::chr($col+2). $row)->getCalculatedValue());
						if ( !$p['width'] || !$p['length'] || !$p['height'] ) break;
						$col+=3;

						// amounts - as many cols as we have in $amounts
						$p['amounts'] = [];
						foreach ( $amounts as $i => $value ) {
							//d($value.":".$sheet->getCell(self::chr($col+$i) . $row)->getCalculatedValue());
							$p['amounts'][$value] = floatval($sheet->getCell(self::chr($col+$i) . $row)->getCalculatedValue());
						}
						$col+=sizeof($amounts);

						// tail
						$p['tail'] = floatval($sheet->getCell(self::chr($col++) . $row)->getCalculatedValue());

						// виды нанесения
						foreach ( $prints as $print ) {
							$p[$print['what']][$print['print']][$print['where']] = floatval($sheet->getCell(self::chr($col++) . $row)->getCalculatedValue());
						}

						// Прочие процедуры
						$p['misc'] = floatval($sheet->getCell(self::chr($col++) . $row)->getCalculatedValue());

						$model['prices'][] = $p;
						$row++;
					} while ( $row < 300 );	// на всякий случай ограничиваем

					$this->json['page']['models'][$modelIndex] = $model;
				}
			}
		}

		// TODO расширить на полных 2 буквенных разряда, а не только на A*
		protected static function chr($col) {
			$str = "";
			if ( $col > ord('Z')) {
				$str = "A";
				$col -= 26;
			}
			$str .= chr($col);
			return $str;
		}
	}
