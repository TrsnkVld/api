<?php
namespace controllers;

require_once "CommonController.php";


use \Bitrix\Main\UserTable;


/* Class UserRegConfirm
 *
 * Принимает:
 * code: string,
 * user_id: integer
 *
 * Возвращает:
 * status: ok | error
 * error?: string
 */

class UserRegConfirm extends CommonController
{
    public $withCache = FALSE;

    public function post()
    {

        $post = $this->postParams();

        $code = $post['code'];
        $user_id = $post['user_id'];

        $user = UserTable::getRowById(intval($user_id));

//        print_r($user);

        if (!$user) {
            $this->json['status'] = 'error';
            $this->json['error'] = 'Такой пользователь не найден';
            return;
        }

        if ($user['ACTIVE'] == 'Y') {
            $this->json['status'] = 'error';
            $this->json['error'] = 'e-mail уже подтверждён';
            return;
        }

        if ($user['CONFIRM_CODE'] != $code) {
//            $this->json['$post'] = $post;
//            $this->json['$user'] = $user;
//            $this->json['$user->CONFIRM_CODE'] = $user['CONFIRM_CODE'];
            $this->json['status'] = 'error';
            $this->json['error'] = 'Неправильный код';
            return;
        }

        $user = new \CUser;
        $fields = [];
        $fields['CONFIRM_CODE'] = '';
        $fields['ACTIVE'] = 'Y';
        $user->Update($user_id, $fields);

        $this->json['status'] = 'ok';
    }

}
