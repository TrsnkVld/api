<?php

	namespace controllers;

	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/User.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Order.php";

	class ConstructorUploadDesign extends CommonController {
		public function post() {
			\CModule::IncludeModule('iblock');

			$params = $_POST;
			//$params = self::$postStream;
			//d($_FILES);
			//d($params);

			// 1. берем  заказ
			$id = intval(decode($params['orderHash']));
			$order = \tools\Order::byId($id);
			if (!$order) throw new \Exception("Order not found by id " . $id);
			//d($order);

			// 3. удалить существующую картинку
			/*foreach ( $order['PROPERTY_DESIGN_FILE_VALUE'] as $imageId ) {
				$image = \CFile::GetFileArray($imageId);
				if ( $image['DESCRIPTION'] == $params['side']) {
					// такая сторона уже существует - удаляем
					\CFile::delete($image['ID']);
				}
			}*/

			// 4. подготовим новую картинку
			$file = $_FILES['file']['tmp_name'];
			// для IE11 необходимо расширение:
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			if (!$ext) {
				$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
				rename($file, $file . "." . $ext);
				$file = $file . "." . $ext;
			}

			// 5. сохряняем новый файл
			$image = \CFile::MakeFileArray($file);
			$image['description'] = $params['side'];
			$image['MODULE_ID'] = "iblock";
			\CIBlockElement::SetPropertyValueCode($id, "DESIGN_FILES", ["VALUE"=>$image]);
			/*$image = \CFile::MakeFileArray($file);
			$props = [
				"DESIGN_FILE" => $image,
			];
			\CIBlockElement::SetPropertyValuesEx($id, \Config::IBLOCK_ID_ORDERS, $props);*/

			// 6. заново взять заказ, на всякий случай
			$order = \tools\Order::byId($id);

			// 7. выдать заказ
			$this->json['page'] = [
				"order" => $order,
			];
		}
	}
