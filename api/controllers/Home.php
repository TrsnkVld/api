<?php

namespace controllers;
require_once "CommonController.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Ship.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Pub.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Hot.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Excursion.php";

class Home extends CommonController
{

    public function get()
    {
        $this->json['page']['ships'] = \tools\Ship::getList();
		$this->json['page']['pubs'] =  \tools\Pub::getList();
		$this->json['page']['hots'] = \tools\Hot::getList();
		$this->json['page']['excursions'] = \tools\Excursion::getList();



  		$res = \CIBlockElement::GetProperty(\Config::IBLOCK_ID_CONTENT, \Config::ID_HOME, array("SORT" => "ASC"), array("CODE" => "SLIDER"));
				//$ar_props = $db_props->Fetch();

		while ($ob = $res->GetNext()){
        		$images[] = \CFile::GetPath($ob['VALUE']);
		}
        $this->json['page']['slider'] = $images;
    }

    protected function initMetas()
    {

    }
}

?>
