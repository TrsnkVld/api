<?php

namespace controllers;

require_once "CommonController.php";

/* Class UserReg
 *
 * Принимает массив с полями:
 * login: string,
 * email: string,
 * password: string,
 * password_check: string,
 *
 * Возвращает:
 * status: ok | error
 * error?: string
 */
class UserReg extends CommonController
{
    public $withCache = FALSE;

    public function post()
    {

        $post = $this->postParams();

            $secret = "6LdJGqQUAAAAAHfVaQfs_w0m5-tKMN2u7zNnt6kx";

            $url = 'https://www.google.com/recaptcha/api/siteverify';
            $data = [
                'secret' => $secret,
                'response' => $post["captcha"]
            ];
            $options = [
            'http' => [
                'method' => 'POST',
                'content' => http_build_query($data)
                ]
            ];
            $context  = stream_context_create($options);
            $verify = file_get_contents($url, false, $context);
            $captcha_success=json_decode($verify);
            if ($captcha_success->success==false) {
//                throw new Exception("User reg incorrect");
                d('User reg incorrect');
            } else if ($captcha_success->success==true) {
            // сохраняем данные, отправляем письма, делаем другую работу. Пользователь не робот
            }

        GLOBAL $USER;

        $arRegResult = $USER->Register($post['login'], "", "", $post['password_check'], $post['password_check'], $post['email']);

        if (isset($arRegResult['TYPE']) && $arRegResult['TYPE'] == 'ERROR') {
            $this->json['status'] = 'error';
            $this->json['error'] = preg_split("/<br>/", $arRegResult['MESSAGE']);
        } else {
            $this->json['status'] = 'ok';
        }
    }

}
