<?php

namespace controllers;

require_once "CommonController.php";


/* Class UserSave
 *
 * Принимает массив с полями:
 * email: string
 * phone: string
 * name: string
 * lastName: string
 * secondName: string
 * country: string
 * state: string
 * city: string
 * street: string
 *
 *
 * Возвращает:
 * status: ok | error
 * error?: string
 */

class UserUpdate extends CommonController
{
    public $withCache = FALSE;

    public function post()
    {
        GLOBAL $USER;

        $post = $this->postParams();


        /*$userArr = [];
        $userArr['EMAIL'] = $post['email'];
        $userArr['PERSONAL_PHONE'] = $post['phone'];
        $userArr['NAME'] = $post['name'];
        $userArr['LAST_NAME'] = $post['lastName'];
        $userArr['SECOND_NAME'] = $post['secondName'];
        $userArr['PERSONAL_COUNTRY'] = $post['country'];
        $userArr['PERSONAL_STATE'] = $post['state'];
        $userArr['PERSONAL_CITY'] = $post['city'];
        $userArr['PERSONAL_STREET'] = $post['street'];
        $userArr['PASSWORD'] = $post['newPassword'];
        $userArr['PASSWORD_CONFIRM'] = $post['confirmNewPassword'];



        $arResult = $USER->Update($USER->GetID(), $userArr);*/

        $fields = Array(
            "EMAIL"             => $post['email'],
            "PERSONAL_PHONE" => $post['phone'],
            "NAME" => $post['name'],
            "LAST_NAME" => $post['lastName'],
            "SECOND_NAME" => $post['secondName'],

        );

        if ( $post['newPassword'] &&  $post['confirmNewPassword']) {

            $fields = Array(
                "PASSWORD" => $post['newPassword'],
                "PASSWORD_CONFIRM" => $post['confirmNewPassword'],

            );
        }

        $USER->Update($USER->GetID(), $fields);

        if (isset($arResult['TYPE']) && $arResult['TYPE'] == 'ERROR') {
            $this->json['status'] = 'error';
            $this->json['error'] = str_replace("<br>", " ", $arResult['MESSAGE']);
        } else {
            $this->json['status'] = 'ok';
        }
    }
}
