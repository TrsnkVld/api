<?php

	namespace controllers;

	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/User.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Order.php";

	class OrderShip extends CommonController {
		public function post() {

			\CModule::IncludeModule('iblock');
			\CModule::IncludeModule('sale');

			$params = self::postParams();

			// test params
			///$params['date'] = '17.04.2020';
            ///$params['time'] = '19:00:00';
            ///$params['email'] = 'aivanov@playnext.ru';


			if (!$params['date'] || !$params['shipId'] || !$params['time'] || !$params['email']) throw new \Exception("Error params");

			$basket = \Bitrix\Sale\Basket::create(SITE_ID);

            $month  =  date('m',strtotime($params['date']));
			$day  =  date("w",strtotime($params['date']));
			$time  =  date("H",strtotime($params['time']));

			//print_r($month);exit;

			// TODO  choose real date
			if ( $day == 5 || $day == 6 || $day == 7 ) $day = 'пт - сб, праздн. дни';
			else $day = "воск - чт";

			if ($month == '06' || $month == '07' || $month == '08') $season = 'ИЮНЬ, ИЮЛЬ, АВГУСТ';
			else $season = 'АПРЕЛЬ, МАЙ, СЕНТЯБРЬ, ОКТЯБРЬ';

			if ($time > 10 && $time < 23) $time = '10:00-23:00';
			else $time = '23:00-10:00';



            // get product (tp)
			$res = \CIBlockElement::GetList(
				['SORT' => 'ASC'],
				[
					'IBLOCK_ID' => \Config::IBLOCK_ID_SHIP_TP,
					'ACTIVE' => 'Y',
					'PROPERTY_DAYS_VALUE' => $day,
					'PROPERTY_SEASON_VALUE' => $season,
					'PROPERTY_TIME_VALUE' => $time,
					'PROPERTY_CML2_LINK' => $params['shipId'],

				],
				false,
				false,
				array("ID","NAME", "CODE", "PROPERTY_SEASON", "PROPERTY_DAYS", "PROPERTY_TIME"),
			);
			$product = $res->GetNext();

			if ( !$product['ID'] )  throw new \Exception("not found tp");

			$db_res = \CPrice::GetList(
        		array(),
        		array(
                "PRODUCT_ID" => $product['ID'],
                "CATALOG_GROUP_ID" => 1
            	)
    		);
			$ar_res = $db_res->Fetch();

			if ($ar_res['PRICE'] > 0) $orderInfo = (array('PRODUCT_ID' => $product['ID'], 'NAME' => $product['NAME'], 'PRICE' => $ar_res['PRICE'], 'CURRENCY' => 'RUB', 'QUANTITY' => 1));


			$item = $basket->createItem("catalog", $orderInfo["PRODUCT_ID"]);

			unset($orderInfo["PRODUCT_ID"]);
			$item->setFields($orderInfo);

			// get user
			$email = $params['email'];
			if ($email) {
						$res = \Bitrix\Main\UserTable::getRow(array(
							'filter' => array('=EMAIL' => $email),
							'select' => array('ID'),
							'order' => array('ID' => 'desc'),
						));
						if ($res['ID']) $userId = intval($res['ID']);
			}
			if (!$userId) {
						$this->userNew = \tools\User::registerAndLogin([
							"name" => trim($params['name']),
							"email" => $email,
							"phone" => trim($params['phone']),
						]);
						$userId = $this->userNew['ID'];
			}
			if (!$userId) throw new \Exception("Could not register user");

			$order = \Bitrix\Sale\Order::create(SITE_ID, $userId);
			$order->setPersonTypeId(1);
			$order->setBasket($basket);

			// set order fields
			$order->setField('USER_DESCRIPTION', $params['text']);
			$propertyCollection = $order->getPropertyCollection();


			foreach ($propertyCollection as $property) {
				if ($property->getField('CODE') == "TIME_ORDER") {
					$property->setValue($params['date']." ".$params['time']);

				}
				if ($property->getField('CODE') == "ORDER_TYPE") {
					$property->setValue($params['сonfirmOptions']);

				}
			}



			$result = $order->save();

			$orderId = $order->getId();
			// send admin ... order module no send
			$admin = \CUser::GetList(($by = "NAME"), ($order = "desc"),array("ID" => \Config::ID_ADMIN),array('SELECT' => array("UF_*")));
            $admin = $admin->GetNext();

			$arEventFields = array(
				"EMAIL_FROM" => \Config::EMAIL_FROM,
				'EMAIL_TO' => $admin['EMAIL'],
				'ORDER_ID' => $orderId,
				'ORDER_URL' => $_SERVER['HTTP_HOST']."/bitrix/admin/sale_order_view.php?ID=".$orderId."&filter=Y&set_filter=Y&lang=ru",
				'HOST' => "http://" . $_SERVER['HTTP_HOST'] . "/auth",
				'SITE_NAME' => $_SERVER['HTTP_HOST'],
			);

			$arrSite = 's1';
			$send = \CEvent::SendImmediate('NEW_ORDER_ADMIN', $arrSite, $arEventFields, 'N');



			$this->json['page']['result'] = "ok"; // TODO

		}
	}

?>