<?php

namespace controllers;

/* Class ReviewAdd
 *
 * Принимает массив с полями:
 * positive: string
 * negative: string
 * comment: string
 * product_id: number
 *
 *
 * Возвращает:
 * status: ok | error
 * error?: string
 */

require_once "CommonController.php";

class ReviewAdd extends CommonController
{
    public $withCache = FALSE;

    public function post()
    {
        global $USER;
        $el = new \CIBlockElement;

        $post = $this->postParams();

        if (!$post['product_id']) throw new \Exception('Ошибка #0');

        $PROP = [];

        $PROP['POSITIVE'] = $post['positive'];
        $PROP['NEGATIVE'] = $post['negative'];
        $PROP['COMMENT'] = $post['comment'];
        $PROP['USER'] = $USER->GetID();
        $PROP['PRODUCT'] = intval($post['product_id']);

        $arLoadProductArray = Array(
            "MODIFIED_BY" => $USER->GetID(),
            "IBLOCK_SECTION_ID" => false,
            "IBLOCK_ID" => 14,
            "PROPERTY_VALUES" => $PROP,
            "NAME" => "Элемент",
            "ACTIVE" => "N",
            "PREVIEW_TEXT" => "",
            "DETAIL_TEXT" => "",
        );

        if ($PRODUCT_ID = $el->Add($arLoadProductArray)) {
            $this->json['result'] = 'ok';
        } else {
            $this->json['result'] = 'error';
            $this->json['error'] = $el->LAST_ERROR;

        }
    }
}
