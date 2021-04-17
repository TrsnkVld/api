<?php

	namespace controllers;

	require_once "CommonController.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/User.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Order.php";

	class OrderExcursion extends CommonController {
		public function post() {

			\CModule::IncludeModule('iblock');
			\CModule::IncludeModule('sale');

			$params = self::postParams();

			//print_r($params);exit;

			// test params
			/*$params['adults'] = array(60 => 2);
			$params['children'] = array(62 => 1);
			$params['foreigners'] = array(61 => 3);
            $params['email'] = 'aivanov@playnext.ru';
   			$params['date'] = '17.04.2020';
            $params['time'] = '19:00:00';  */

            $offersId = array();

            if ( sizeof($params['adults']) ) {            	$offersIdCount[array_keys($params['adults'])[0]] = array_shift($params['adults']);            }
            if ( sizeof($params['children']) ) {
            	$offersIdCount[array_keys($params['children'])[0]] = array_shift($params['children']);
            }
            if ( sizeof($params['foreigners']) ) {
            	$offersIdCount[array_keys($params['foreigners'])[0]] = array_shift($params['foreigners']);
            }
            $offersId = array_keys($offersIdCount);

            $all_count = array_sum($offersIdCount);

            //QUANTITY position, get 1 of 3
            $QUANTITY = \CCatalogProduct::GetByID(array_keys($offersIdCount)[0]);
            if ($QUANTITY['QUANTITY']>0){            	// done            }
            else {            	throw new \Exception("QUANTITY miss");            }


			if (!sizeof($offersId) || !$params['email'] || !$params['date'] || !$params['time']) throw new \Exception("Error params");

			$basket = \Bitrix\Sale\Basket::create(SITE_ID);

			// get product (tp)
			$res = \CIBlockElement::GetList(
				['SORT' => 'ASC'],
				[
					'IBLOCK_ID' => \Config::IBLOCK_ID_EXCS_TP,
					'ACTIVE' => 'Y',
					'ID' => $offersId, // id excursion not need, tp id

				],
				false,
				false,
				array("ID","NAME", "CODE", "TIME", "TYPE"),
			);
			$productCount = 0;
			while ($product = $res->GetNext()){				$db_res = \CPrice::GetList(
        		array(),
        		array(
                "PRODUCT_ID" => $product['ID'],
                "CATALOG_GROUP_ID" => 1
            	)
	    		);
				$ar_res = $db_res->Fetch();

				if ($ar_res['PRICE'] > 0) {					$orderInfo = (array('PRODUCT_ID' => $product['ID'], 'NAME' => $product['NAME'], 'PRICE' => $ar_res['PRICE'], 'CURRENCY' => 'RUB', 'QUANTITY' => $offersIdCount[$product["ID"]]));
					$productCount++;
				}


				$item = $basket->createItem("catalog", $orderInfo["PRODUCT_ID"]);

				// change count
				$QUANTITY = \CCatalogProduct::GetByID($product['ID']);
				$arFields = array('QUANTITY' => $QUANTITY['QUANTITY'] - $all_count); // all for all
				\CCatalogProduct::Update($product['ID'], $arFields);

				unset($orderInfo["PRODUCT_ID"]);
				$item->setFields($orderInfo);


			}
			if ( $productCount == 0 ) throw new \Exception("Products null");

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