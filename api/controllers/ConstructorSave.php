<?php

	namespace controllers;

	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/User.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Order.php";

	class ConstructorSave extends CommonController {
		public function post() {

			\CModule::IncludeModule('iblock');

			$params = self::postParams();
			//d($params);

			// 0. валидуем
			if (!$params['type'] || !$params['model']) throw new \Exception("Bad params");

			// 1. берем заказ
			$id = decode($params['orderHash']);
			$n = NULL;
			if (!$id) {
				// если нет - создаем новый заказ
				$n = str_pad(rand(0, 999), 3, "0", STR_PAD_LEFT) . "/" . time();
				$el = new \CIBlockElement;
				$arFields = Array(
					//"IBLOCK_CODE" => \Config::IBLOCK_CODE_ORDERS,
					"IBLOCK_ID" => \Config::IBLOCK_ID_ORDERS,
					"ACTIVE" => 'N',
					"NAME" => 'Заказ ' . $n,
					"PROPERTY_VALUES" => [
						"NUMBER" => "" . $n
					]
				);
				$id = $el->Add($arFields);
				if (!$id) throw new \Exception("Could not create order: " . $el->LAST_ERROR);
			}
			$order = \tools\Order::byId($id);
			if (!$order) throw new \Exception("Order not found by id " . $id);
			//d($order);

			// 2. сохряняем данные

			// "прямые" свойства
			$names = [
				"TYPE" => "type",
				"MODEL" => "model",
				"LENGTH" => "length",
				"WIDTH" => "width",
				"HEIGHT" => "height",
				"LAMINATION" => "lamination",
				"TAIL" => "tail",
				"FILLING" => "filling",
				"AMOUNT" => "amount",
				"PRICE" => "price",
				"CUSTOM_PRICE" => "customPrice",
				"IS_PICKUP" => "isPickup",
				"IS_URGENT" => "isUrgent",
				"IS_CUSTOM" => "isCustom",
				"IS_PRELIMINARY" => "isPreliminary",
				"PAYMENT" => "payment",
				"COMMENT" => "comment",
			];
			$props = [];
			foreach ($names as $key => $name) {
				$props[$key] = $params[$name];
			}

			// дочерние свойства
			$props["MATERIAL_OUTSIDE"] = $params['materials']['outside'];
			$props["MATERIAL_INSIDE"] = $params['materials']['inside'];
			$props["CITY"] = $params['address']['city'];
			$props["ADDRESS"] = $params['address']['address'];
			$props["OFFICE"] = $params['address']['office'];
			$props["POSTCODE"] = $params['address']['postcode'];
			$props["SIDES_JSON"] = json_encode($params['sides']);
			$props["JSON"] = json_encode($params);

			// обработать булеаны и именованные списки
			$enumCodes = ["IS_PICKUP", "IS_URGENT", "IS_CUSTOM", "IS_PRELIMINARY", "LAMINATION"];
			foreach ($enumCodes as $code) {
				//p($code.":".$props[$code]);
				if ( !$props[$code] ) {
					$props[$code] = "";
					continue;
				}

				$enumRes = \CIBlockPropertyEnum::GetList([], ["IBLOCK_ID" => \Config::IBLOCK_ID_ORDERS, "CODE" => $code]);
				if ( preg_match("/^IS_.+/", $code) ) {
					//p("Set boolean: ".$props[$code]);
					// булеан
					$enum = $enumRes->GetNext();
					if (!$enum) continue;
					$props[$code] = $enum['ID'];
				}
				else {
					//p("Set list: ".$props[$code]);
					// список
					while ( $enum = $enumRes->GetNext() ) {
						//d($enum);
						if ( $enum['VALUE'] != $props[$code] ) continue;
						$props[$code] = $enum['ID'];
						break;
					}
				}
			}

			// обработать цены
			$props["PRICE"] = round($props['PRICE'], 2);
			$props["CUSTOM_PRICE"] = round($props['CUSTOM_PRICE'], 2);
			//d($props);

			\CIBlockElement::SetPropertyValuesEx($id, \Config::IBLOCK_ID_ORDERS, $props);

			// 3. если финальное сохранение - регим пользователя, активируем заказ
			$userId = NULL;
			$this->isNewUser = false;
			if ($params['isFinal']) {
				$userId = $order['PROPERTY_USER_VALUE'];
				if (!$userId) {
					// пробуем найти пользователя по емейлу:
					$email = trim($params['contact']['email']);
					if ($email) {
						$res = \Bitrix\Main\UserTable::getRow(array(
							'filter' => array('=EMAIL' => $email),
							'select' => array('ID'),
							'order' => array('ID' => 'desc'),
						));
						if ($res['ID']) $userId = intval($res['ID']);
					}
					if (!$userId) {
						// создаем нового юзера
						$this->userNew = \tools\User::registerAndLogin([
							"name" => trim($params['contact']['name']),
							"email" => $email,
							"phone" => trim($params['contact']['phone']),
						]);
						$userId = $this->userNew['ID'];
						$this->isNewUser = true;
					}
				}
				if (!$userId) throw new \Exception("Could not register user");
				//$user = \tools\User::byId($userId);
				//d($user);

				\CIBlockElement::SetPropertyValuesEx($id, \Config::IBLOCK_ID_ORDERS, ["USER" => $userId]);

				$fields = Array(
					"ACTIVE" => 'Y',
				);
				//d($fields);
				$el = new \CIBlockElement;
				$res = $el->Update($id, $fields);
				if (!$res) throw new \Exception("Could not save order: " . $el->LAST_ERROR);
			}

			// 4. заново взять заказ, чтобы все обновить
			$order = \tools\Order::byId($id);

			// 5. выслать уведомления
			if ($params['isFinal']) {
				$this->sendOrderMailAdmin($order);// TODO 1. выслать почту администратору о готовности заказа, оформить в отдельный метод, передать ему $order и $userId
				$this->sendOrderMailUser($order, $userId);// TODO 2. выслать почту клиенту с благодарностью за заказ, оформить в отдельный метод, передать ему $order и $userId
			}

			// 6. выдать обновленный заказ
			$this->json['page'] = [
				"order" => $order,
			];
		}

		protected function sendOrderMailUser($order, $userId) {
			if (!$order || !$userId) return false;

			$user = \CUser::GetList(($by = "NAME"), ($order2 = "desc"), array("ID" => $userId), array('SELECT' => array("UF_*")));
			$user = $user->GetNext();

			//			print_r($order);
			//print_r($user);
			//exit;

			if (!$user) return false;

			$arEventFields = array(
				"EMAIL_FROM" => \Config::EMAIL_FROM,
				'NAME' => $user['NAME'],
				'EMAIL' => $user['EMAIL'],
				'EMAIL_TO' => $user['EMAIL'],
				//'PASSWORD' => $user['PASSWORD'],
				'ORDER_LINK' => \tools\Order::linkForHash($order['HASH']),
				'HOST' => "http://" . $_SERVER['HTTP_HOST'] . "/auth",
				'SITE_NAME' => $_SERVER['HTTP_HOST'],
				'NUMBER' => $order['PROPERTY_NUMBER_VALUE'],
				'TEXT' => ($order['IS_PRELIMNARY'] ? ("Ваше предварительное обращение " . $order['PROPERTY_NUMBER_VALUE'] . " принято.") : ("Ваш заказ " . $order['PROPERTY_NUMBER_VALUE'] . " принят в работу.")),
				'TEXT_2' => ($this->isNewUser ? ("Пожалуйста, используйте следующие данные для авторизации:\n\nЛогин: " . $user['EMAIL'] . "\nПароль: " . $this->userNew['PASSWORD'] . "\n" . "http://" . $_SERVER['HTTP_HOST'] . "/auth") : ""),
			);


			$arrSite = 's1';
			$send = \CEvent::SendImmediate('USER_ORDER', $arrSite, $arEventFields, 'N');
		}

		protected function sendOrderMailAdmin($order) {
			if (!$order) return false;


			$options = \CIBlockElement::GetList(array(), array("IBLOCK_ID" => 16, "ID" => 609), false, false, array("PROPERTY_EMAIL",));
			$options = $options->GetNext();

			//print_r($order);exit;

			$type = \CIBlockElement::GetList(array(), array("IBLOCK_ID" => 5, "ID" => $order['PROPERTY_TYPE_VALUE']), false, false, array("NAME",));
			$type = $type->GetNext();

			$model = \CIBlockElement::GetList(array(), array("IBLOCK_ID" => 6, "ID" => $order['PROPERTY_MODEL_VALUE']), false, false, array("NAME","PROPERTY_IS_LWH"));
			$model = $model->GetNext();

			$inside = \CIBlockElement::GetList(array(), array("IBLOCK_ID" => 7, "ID" => $order['PROPERTY_MATERIAL_INSIDE_VALUE']), false, false, array("NAME","IBLOCK_ID","IBLOCK_SECTION_ID"));
			$inside = $inside->GetNext();

			$outside = \CIBlockElement::GetList(array(), array("IBLOCK_ID" => 7, "ID" => $order['PROPERTY_MATERIAL_OUTSIDE_VALUE']), false, false, array("NAME","IBLOCK_ID","IBLOCK_SECTION_ID"));
			$outside = $outside->GetNext();

			if ( $inside['NAME'] ) {
				$navChain = \CIBlockSection::GetNavChain($inside['IBLOCK_ID'], $inside['IBLOCK_SECTION_ID']);
            	$arNav=$navChain->GetNext();
            	$insideFather = $arNav["NAME"];
            }
            if ( $outside['NAME'] ) {
				$navChain = \CIBlockSection::GetNavChain($outside['IBLOCK_ID'], $outside['IBLOCK_SECTION_ID']);
            	$arNav=$navChain->GetNext();
            	$outsideFather = $arNav["NAME"];
            }

			$lenta = \CIBlockElement::GetList(array(), array("IBLOCK_ID" => 14, "ID" => $order['PROPERTY_TAIL_VALUE']), false, false, array("NAME",));
			$lenta = $lenta->GetNext();

			$filling = \CIBlockElement::GetList(array(), array("IBLOCK_ID" => 15, "ID" => $order['PROPERTY_FILLING_VALUE']), false, false, array("NAME","IBLOCK_ID","IBLOCK_SECTION_ID"));
			$filling = $filling->GetNext();

            if ( $filling['NAME'] ) {
				$navChain = \CIBlockSection::GetNavChain($filling['IBLOCK_ID'], $filling['IBLOCK_SECTION_ID']);
            	$arNav=$navChain->GetNext();
            	$fillingFather = $arNav["NAME"];
            }

			// get userId, no in $order
			$orderUser = \CIBlockElement::GetList(array(), array("IBLOCK_ID" => 13, "ID" => $order['~ID']), false, false, array("PROPERTY_USER",));
			$orderUser = $orderUser->GetNext();

			$user = \CUser::GetList(($byu = "NAME"), ($ou = "desc"), array("ID" => $orderUser['PROPERTY_USER_VALUE']));
			$user = $user->GetNext();


			if (!$options) return false;

			if ($order['PROPERTY_IS_PRELIMINARY_VALUE'] == "Y" && $order['PROPERTY_CUSTOM_PRICE_VALUE'] > 0){				$priceLabel = "'Обсудить цену' " . number_format($order['PROPERTY_CUSTOM_PRICE_VALUE'], 0, ",", " ") . " руб.";			}
			elseif ($order['PROPERTY_CUSTOM_PRICE_VALUE'] > 0) {				$priceLabel = "Стоимость заказа " . number_format($order['PROPERTY_CUSTOM_PRICE_VALUE'], 0, ",", " "). " руб.";			}
			else {				$priceLabel = "-";			}

			if ($model['PROPERTY_IS_LWH_VALUE'] == "Y"){  // ДхШхВ (иначе как ШхДхВ)            	$labelLWH = "ДхШхВ";
            	$labelLWHValue = $order['PROPERTY_LENGTH_VALUE'] . "x" . $order['PROPERTY_WIDTH_VALUE'] . "x" . $order['PROPERTY_HEIGHT_VALUE'];			}
			else {				$labelLWH = "ШхДхВ";
				$labelLWHValue = $order['PROPERTY_WIDTH_VALUE']. "x". $order['PROPERTY_LENGTH_VALUE'] . "x" . $order['PROPERTY_HEIGHT_VALUE'];			}

			$table = NULL;

			$table = "<table border='1'>";
			$table .= "<tr><td>Номер заказа</td><td>" . $order['PROPERTY_NUMBER_VALUE'] . "</td></tr>";
			$table .= "<tr><td>Дата заказа</td><td>" . $order['DATE_CREATE'] . "</td></tr>";
			$table .= "<tr><td>Предварительное обращение</td><td>" .($order['PROPERTY_IS_PRELIMINARY_VALUE'] == "Y"?"Да":"Нет"). "</td></tr>";
			$table .= "<tr><td>Стоимость</td><td>" .$priceLabel. "</td></tr>";
			$table .= "<tr><td>Срочный</td><td>" . ($order["PROPERTY_IS_URGENT_VALUE"] == "Y" ? "Да" : "Нет") . "</td></tr>";
			$table .= "<tr><td>Количество</td><td>" . (number_format($order["PROPERTY_AMOUNT_VALUE"], 0, ",", " ")) . "</td></tr>";
			if ($type['NAME']) $table .= "<tr><td>Категория, модель</td><td>" . $type['NAME'] . ", " . $model['NAME'] . "</td></tr>";
			if ($order['PROPERTY_LENGTH_VALUE']) $table .= "<tr><td>Размер (".$labelLWH.")</td><td>" . $labelLWHValue . "</td></tr>";
			if ($inside["NAME"]) $table .= "<tr><td>Материал внутри</td><td>" .$insideFather.", ".$inside["NAME"] . "</td></tr>";
			if ($outside["NAME"]) $table .= "<tr><td>Материал снаружи</td><td>" .$outsideFather.", ".$outside["NAME"] . "</td></tr>";
			$table .= "<tr><td>Лента</td><td>" . ($lenta["NAME"]?$lenta["NAME"]:"Нет") . "</td></tr>";
			$table .= "<tr><td>Ламинация</td><td>" . ($order['PROPERTY_LAMINATION_VALUE']?$order['PROPERTY_LAMINATION_VALUE']:"Нет") . "</td></tr>";
			if ($filling["NAME"]) $table .= "<tr><td>Наполнение</td><td>" .$fillingFather.", ".$filling["NAME"] . "</td></tr>";
			$table .= "<tr><td>Необходимо разработать дизайн</td><td>" . ($order["PROPERTY_IS_CUSTOM_VALUE"] == "Y" ? "Да" : "Нет") . "</td></tr>";
			$table .= "<tr><td>Самовывоз</td><td>" . ($order['PROPERTY_IS_PICKUP_VALUE'] == "Y" ? "Да" : "Нет") . "</td></tr>";
			$table .= "<tr><td>Имя</td><td>" . $user['NAME'] . "</td></tr>";
			$table .= "<tr><td>Email</td><td>" . $user['EMAIL'] . "</td></tr>";
			$table .= "<tr><td>Телефон</td><td>" . $user['PHONE'] . "</td></tr>";
			if ($order['PROPERTY_IS_PICKUP_VALUE'] != "Y") $table .= "<tr><td>Адрес</td><td>" . $user['PROPERTY_CITY_VALUE'] . " " . $user['PROPERTY_ADDRESS_VALUE'] . " " . $user['PROPERTY_OFFICE_VALUE'] . " " . $user['PROPERTY_POSTCODE_VALUE'] . "</td></tr>";

			$table .= "<tr><td>Вид оплаты</td><td>" . $order['PROPERTY_PAYMENT_VALUE'] . "</td></tr>";
			$table .= "<tr><td>Комментарий</td><td>" . $order['PROPERTY_COMMENT_VALUE'] . "</td></tr>";
			$table .= "<tr><td>Ссылка на заказ</td><td>" . \tools\Order::linkForHash($order['HASH']) . "</td></tr>";

			$table .= "</table>";


			$arEventFields = array(
				"EMAIL_FROM" => \Config::EMAIL_FROM,
				"EMAIL_TO" => $options['PROPERTY_EMAIL_VALUE'],

				"ORDER_ID" => $order['PROPERTY_NUMBER_VALUE'],

				'HOST' => "http://" . $_SERVER['HTTP_HOST'] . "/auth",
				'SITE_NAME' => $_SERVER['HTTP_HOST'],

				'TABLE' => $table,

			);
			$file = \CFile::GetPath($arFields['PROPERTY_DESIGN_FILES_VALUE']);
			$arrSite = 's1';
			$send = \CEvent::SendImmediate('ADMIN_ORDER', $arrSite, $arEventFields, 'N', '', array($file));
		}
	}

?>