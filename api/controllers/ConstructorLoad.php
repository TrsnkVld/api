<?php

	namespace controllers;

	require_once "OrderType.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/User.php";
	require_once $_SERVER['DOCUMENT_ROOT'] . "/../api/tools/Order.php";

	/**
	 * Отдает данные заказа для конструктора.
	 * Основан на OrderType - отдает также все данные по типу картона и его моделям.
	 * Class ConstructorLoad
	 * @package controllers
	 */
	class ConstructorLoad extends OrderType {

		// this should not cache, but should be GET
		public $withCache = FALSE;

		public function get() {
			$params = $_GET;
			//d($params);

			// 1. берем заказ
			$id = intval(decode($params['orderHash']));
			$order = \tools\Order::byId($id);
			if (!$order) throw new \Exception("Order not found by id " . $id);
			//d($order);

			// 2. инициализируем тип картона
			$_GET['typeId'] = $order['PROPERTY_TYPE_VALUE'];
			parent::get();

			// 3. присоединить заказ к ответу
			$this->json['page']['order'] = $order;
			$this->json['page']['title'] = "Редактирование заказа ".$order['NUMBER'];
		}
	}
