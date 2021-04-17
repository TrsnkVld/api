<?php

	namespace tools;

	class Order {

		public static function byId($id) {
			\CModule::IncludeModule('iblock');

			$res = \CIBlockElement::GetList(
				['SORT' => 'ASC'],
				[
					'IBLOCK_CODE' => \Config::IBLOCK_CODE_ORDERS,
					//'ACTIVE' => 'Y',
					'=ID' => $id
				],
				false,
				false,
				self::selectedFields()
			);

			$order = $res->GetNext();
			if (!$order) return NULL;
			$images = [];
			foreach ($order['PROPERTY_SIDE_IMAGES_VALUE'] as $imageId) {
				$image = \CFile::GetFileArray($imageId);
				//d($image);
				//$imageURL = \CFile::GetPath($imageId);
				$images[$image['DESCRIPTION']] = [
					"id" => $image['ID'],
					"url" => $image['SRC'],
					"width" => $image['WIDTH'],
					"height" => $image['HEIGHT'],
				];

				// TODO fixme
				$order['images'] = $images;
			}
			//d($order);

			// шифруем order ID для url
			$order['HASH'] = encode($id);
			unset($order['ID']);

			// диджей сторон
			if (isset($order['~PROPERTY_SIDES_JSON_VALUE'])) {
				$order['PROPERTY_SIDES_VALUE'] = json_decode($order['~PROPERTY_SIDES_JSON_VALUE']);
				unset($order['PROPERTY_SIDES_JSON_VALUE']);
			}

			return $order;
		}

		public static function selectedFields() {
			return [
				'ID',
				'DATE_CREATE',
				'PROPERTY_IS_PRELIMINARY',
				'PROPERTY_CUSTOM_PRICE',
				//'IBLOCK_ID',
				//'IBLOCK_CODE',
				'NAME',
				'ACTIVE',
				'PROPERTY_NUMBER',
				'PROPERTY_TYPE',
				'PROPERTY_MODEL',
				'PROPERTY_LENGTH',
				'PROPERTY_WIDTH',
				'PROPERTY_HEIGHT',
				'PROPERTY_MATERIAL_OUTSIDE',
				'PROPERTY_MATERIAL_INSIDE',
				'PROPERTY_LAMINATION',
				'PROPERTY_TAIL',
				'PROPERTY_FILLING',
				'PROPERTY_AMOUNT',
				'PROPERTY_IS_CUSTOM',
				'PROPERTY_PRICE',
				'PROPERTY_IS_PICKUP',
				'PROPERTY_CITY',
				'PROPERTY_ADDRESS',
				'PROPERTY_OFFICE',
				'PROPERTY_POSTCODE',
				'PROPERTY_SIDES_JSON',
				'PROPERTY_IS_URGENT',
				'PROPERTY_SIDE_IMAGES',
				//'PROPERTY_USER',	// не выдаем userId в браузер!
				//'PROPERTY_JSON',
			];
		}

		/**
		 * Формирует корректную ссылку на заказ по хэшу.
		 * @param $orderHash
		 * @return string
		 */
		public static function linkForHash($orderHash) {
			return "http://" . $_SERVER['HTTP_HOST'] . "/constructor/stored/" . $orderHash;
		}
	}
