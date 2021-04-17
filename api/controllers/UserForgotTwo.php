<?php

namespace controllers;

require_once "CommonController.php";

use \Bitrix\Main\UserTable;


/* Class UserRegConfirmTwo
 *
 * Принимает:
 * checkword: string,
 * login: string
 * password: string
 * password_check: string
 *
 * Возвращает:
 * status: ok | error
 * error?: string
 */

class UserForgotTwo extends CommonController
{
    public $withCache = FALSE;

    public function post()
    {
        GLOBAL $USER;

        $post = $this->postParams();

        $checkword = $post['checkword'];
        $login = $post['login'];

        $arResult = $USER->ChangePassword($login, $checkword, $post['password'], $post['password_check']);

        if (isset($arResult['TYPE']) && $arResult['TYPE'] == 'ERROR') {
            $this->json['status'] = 'error';
            $this->json['error'] = str_replace("<br>", " ", $arResult['MESSAGE']);
        } else {
            $this->json['status'] = 'ok';
        }
    }

}
