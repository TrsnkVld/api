<?php
namespace controllers;

require_once "CommonController.php";

use \Bitrix\Main\UserTable;
use \Bitrix\Main\Entity\Query;

/* Class UserForgot
 *
 * Принимает массив с полями:
 * email: string,
 *
 * Возвращает:
 * status: ok | error
 * error?: string
 */
class UserForgot extends CommonController
{
    public $withCache = FALSE;


    public function post()
    {
        $post = $this->postParams();

        $user = UserTable::getRow(Array(
            'filter' => [
                '=EMAIL' => $post['email']
            ]
        ));

//        $q = new Query(UserTable::getEntity());
//        $q->setSelect(['*']);
//        $q->setFilter(['EMAIL' => $post['email']]);
//        $result = $q->exec();
//
//        $this->json['debug'] = $result;

        if (!$user) {
            $this->json['status'] = 'error';
            $this->json['error'] = 'Такой пользователь не найден';
            return;
        }

        GLOBAL $USER;

        $arResult = $USER->SendPassword($user->LOGIN, $post['email']);

        if($arResult["TYPE"] != "OK") {
            $this->json['status'] = 'error';
            $this->json['error'] =  "Введенные логин (e-mail) не найдены.";
            return;
        }

//        $this->json['debug'] = $post;
        $this->json['status'] = 'ok';
    }
}