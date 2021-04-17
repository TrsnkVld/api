<?php
namespace controllers;

require_once "CommonController.php";


/* Class UserLogout
 *
 * Возвращает:
 * status: ok
 */
class UserLogout extends CommonController
{
    public $withCache = FALSE;


    public function post()
    {
        global $USER;

        $USER->Logout();

        $this->json['status'] = 'ok';
    }
}