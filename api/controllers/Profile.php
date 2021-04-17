<?php

namespace controllers;

require_once "CommonController.php";


class Profile extends CommonController
{
    public $withCache = FALSE;

    public function get()
    {
        parent::get();

        GLOBAL $USER;

        \Bitrix\Main\Loader::includeModule("sale");
        \Bitrix\Main\Loader::includeModule("catalog");

        $user = [];

        // some user
        $userArr = \CUser::GetByID($USER->GetID());
        $userArr = $userArr->Fetch();

        if (!$userArr) throw new \Exception('Пользователь не авторизован');


        // user shop values
        $csale = new \CSaleOrderUserProps();
        $db_sales = $csale->GetList(
            array("DATE_UPDATE" => "DESC"),
            array("USER_ID" => $USER->GetID())
        );
        if ($ar_sales = $db_sales->Fetch()) {
            $user['shop_arr'] = $ar_sales;
        }

        // deliviries
        $arDeliv = [];
        $objDeliv = \Bitrix\Sale\Delivery\Services\Manager::getActiveList();
        foreach ($objDeliv as $k => $d) {
            $arDeliv[$k] = [
                'id' => $d['ID'],
                'name' => $d['NAME'],
                'descr' => $d['DESCRIPTION'],
                'currency' => $d['CURRENCY']
            ];
        }

        // payments
        $arPay = [];
        $objPayments = \Bitrix\Sale\PaySystem\Manager::getList();
        foreach ($objPayments as $k => $p) {
            $arPay[$k] = [
                'id' => $p['ID'],
                'name' => $p['NAME'],
                'descr' => $p['DESCRIPTION'],
                'currency' => $p['CURRENCY'],
            ];
        }

        // statuses, TODO: почему то не находит класс, но он есть!
//        $arStatus = [];
//        $objStatus = \Bitrix\Sale\StatusBase::getAllStatuses(true);
//        foreach ($objStatus as $k => $p) {
//            $arStatus[$k] = [
//                '$p' => $p,
//                'id' => $p['ID'],
//            ];
//        }


        // orders
        $user['orders'] = [];
        $arFilter = Array(
            "USER_ID" => $USER->GetID(),
        );
        $rsSales = \CSaleOrder::GetList(array("DATE_INSERT" => "DESC"), $arFilter);
        while ($arSales = $rsSales->Fetch()) {
            $dbItemsInOrder = \CSaleBasket::GetList(array("ID" => "ASC"), array("ORDER_ID" => $arSales['ID']));

            $arItems = [];
            while ($objItems = $dbItemsInOrder->Fetch()) {
                $arItems[] = $objItems;
            }

            $items = [];
            foreach ($arItems as $i) {
                $items[] = [
                    'id' => $i['ID'],
                    'name' => $i['NAME'],
                    'quantity' => $i['QUANTITY'],
                    'price' => $i['PRICE'],
                    'path' => $i['DETAIL_PAGE_URL']
                ];
            }

            $status = \CSaleStatus::GetByID($arSales['STATUS_ID']);

            $user['orders'][] = [
//                '$arSales' => $arSales,
                'is_completed' => ($arSales['STATUS_ID'] == 'Y' || $arSales['STATUS_ID'] == 'S' || $arSales['STATUS_ID'] == 'F' || $arSales['STATUS_ID'] == 'O'),
                'status_id' => ($arSales['STATUS_ID']),
                'status_name' => $status['NAME'],
                'delivery' => $arDeliv[$arSales['DELIVERY_ID']],
                'pay_system' => $arPay[$arSales['PAY_SYSTEM_ID']],
                'id' => $arSales['ID'],
                'date' => FormatDate(array(
                    "tommorow" => "tommorow",
                    "today" => "today",
                    "yesterday" => "yesterday",
                    "" => 'j F Y',
                ), MakeTimeStamp($arSales['DATE_INSERT']), time()),
                'items' => $items,
            ];
        }

        // mega array
        $user['login'] = $USER->GetLogin();
        $user['id'] = $USER->GetID();

        $user['email'] = $userArr['EMAIL'];
        $user['phone'] = $userArr['PERSONAL_PHONE'];
        $user['name'] = $userArr['NAME'];
        $user['lastName'] = $userArr['LAST_NAME'];
        $user['secondName'] = $userArr['SECOND_NAME'];
        /*$user['country'] = $userArr['PERSONAL_COUNTRY'];
        $user['state'] = $userArr['PERSONAL_STATE'];
        $user['city'] = $userArr['PERSONAL_CITY'];
        $user['street'] = $userArr['PERSONAL_STREET'];*/

        $user['password'] = $userArr['NEW_PASSWORD'];
        $user['password_new'] = $userArr['NEW_PASSWORD_CONFIRM'];



        $user['sessid'] = bitrix_sessid();

        $this->json['page']['user'] = $user;
    }
}
