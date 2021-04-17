<?php

	namespace controllers;

	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/User.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Order.php";

	class ConstructorUploadImage extends CommonController {
		public function post() {
			\CModule::IncludeModule('iblock');

			$params = $_POST;
			//$params = self::$postStream;
			//d($_FILES);
			//d($params);

			// 0. проверяем входные данные
			if (!$params['side']) throw new \Exception("Side is not specified");

			// 1. берем заказ
			$id = intval(decode($params['orderHash']));
			$order = \tools\Order::byId($id);
			if (!$order) throw new \Exception("Order not found by id " . $id);
			//d($order);

			// 2. берем или регим пользователя
			/*$userId = $order['PROPERTY_USER_VALUE'];
			if (!$userId) {
				$userId = \tools\User::registerAndLogin([
					"name" => "Пользователь",
					"nameLast" => $order['NAME']
				]);
			}
			if (!$userId) throw new \Exception("Could not register user");*/
			//$user = \tools\User::byId($userId);
			//d($user);

			// 3. удалить существующую картинку
			foreach ($order['PROPERTY_SIDE_IMAGES_VALUE'] as $imageId) {
				$image = \CFile::GetFileArray($imageId);
				if ($image['DESCRIPTION'] == $params['side']) {
					// такая сторона уже существует - удаляем
					\CFile::delete($image['ID']);
				}
			}

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
			\CIBlockElement::SetPropertyValueCode($id, "SIDE_IMAGES", ["VALUE"=>$image]);

			// 6. заново взять заказ, чтобы обновить URL-и картинок
			$order = \tools\Order::byId($id);
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
			}

			// 7. выдать обновленный заказ
			$this->json['page'] = [
				//"userId" => $userId,
				"order" => $order,
				"image" => $images[$params['side']],
			];
		}
	}
