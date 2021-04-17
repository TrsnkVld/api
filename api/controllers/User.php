<?php

	namespace controllers;

	require_once "CommonController.php";
	require_once "Catalog.php";
	require_once "../api/tools/Product.php";
	require_once "../api/Config.php";

	/**
	 * Инициализирует JSON в ключе 'user' индивидуальными данными текущего пользователя.
	 * Это его сессия, корзина и т.п.
	 * Это не кэшируется!
	 * Class UserData
	 * @package controllers
	 */
	class User extends CommonController {

		// для тестирования:
		public function get() {
			$this->post();
		}

		public function post() {
			$this->initUser();
			$this->initBasket();
			$this->initFavorites();
			$this->initCoupon();

			// определяем - пришел ли пользователь по UTM-метке, показывающей скидки
			if (\Bitrix\Main\Loader::IncludeModule("sotbit.price")) {
				$priceChanges = \SotbitPriceCondition::GetChanges();
				if ( sizeof($priceChanges) ) {
					$this->json['user']['withUTM'] = true;
				}
			}
			//else throw new \Exception("sotbit.price not included!");
		}

		private function initUser() {
            // some user
            $userArr = \CUser::GetByID(self::$USER->GetID());
            $userArr = $userArr->Fetch();

            //if (!$userArr) throw new \Exception('Пользователь не авторизован');

            // mega array
            $user['login'] = self::$USER->GetLogin();
            $user['id'] = self::$USER->GetID();

            $user['email'] = $userArr['EMAIL'];
            $user['phone'] = $userArr['PERSONAL_PHONE'];
            $user['name'] = $userArr['NAME'];
            $user['lastName'] = $userArr['LAST_NAME'];
            $user['secondName'] = $userArr['SECOND_NAME'];
            /*$user['country'] = $userArr['PERSONAL_COUNTRY'];
            $user['state'] = $userArr['PERSONAL_STATE'];
            $user['city'] = $userArr['PERSONAL_CITY'];
            $user['street'] = $userArr['PERSONAL_STREET'];*/

		    $this->json['user']['sessid'] = bitrix_sessid();
			if (self::$USER->IsAuthorized()) {
				$this->json['user']['isAuthed'] = true;
				$this->json['user']['user'] = $user;
				//$this->json['user']['name'] = (self::$USER->GetFullName());
			}
		}

		protected function initBasket() {
			\Bitrix\Main\Loader::includeModule("sale");
			\Bitrix\Main\Loader::includeModule("catalog");

			$basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());
			$price = $basket->getPrice(); // Цена с учетом скидок (кроме скидки по купону)
			$fullPrice = $basket->getBasePrice(); // Цена без учета скидок
			$weight = $basket->getWeight(); // Общий вес корзины
			//$orderBasket = $basket->getOrderableItems();
			$basketItems = $basket->getBasketItems(); // массив объектов Sale\BasketItem

			// применение скидок по купону (если введен):
			$discounts = \Bitrix\Sale\Discount::buildFromBasket($basket, new \Bitrix\Sale\Discount\Context\Fuser($basket->getFUserId(true)));
			$discountPrices = [];
			if ( $discounts ) {
				$discounts->calculate();
				$result = $discounts->getApplyResult(true);
				$discountPrices = $result['PRICES']['BASKET'];
			}
			//d($prices);

			$items = [];
			$offerIds = [];
			//d($r);
			//d("Price: ".$price.", ".$fullPrice);
			foreach ($basketItems as $item) {
				//d($item);
				//$fields = $item->getFields();
				//d($fields);
				/*$item->getId();         // ID записи корзины
				$item->getProductId();  // ID товара
				$item->getPrice();      // Цена за единицу
				$item->getQuantity();   // Количество
				$item->getFinalPrice(); // Сумма
				$item->getWeight();     // Вес
				$item->getField('NAME');// Любое поле записи корзины
				$item->canBuy();        // true, если доступно для покупки
				$item->isDelay();       // true, если отложено*/
				$items[] = [
					'basketId' => $item->getId(),
					'sizeId' => $item->getProductId(),
					'price' => $item->getPrice(),
					//'price' => $prices[$item->getId()]['PRICE'],
					'priceBase' => $item->getBasePrice(),
					//'priceBase' => $prices[$item->getId()]['BASE_PRICE'],
					'discount' => $item->getDiscountPrice(),
					'priceWithCouponDiscount' => $discountPrices[$item->getId()]['PRICE'],
					//'discount' => $prices[$item->getId()]['DISCOUNT'],
					'amount' => $item->getQuantity(),
					'name' => $item->getField('NAME'),
					//'properties' => \Bitrix\Sale\BasketPropertiesCollection::load($item)

				];
				$offerIds[$item->getProductId()] = $item->getProductId();
			}

			// добавляем описание товаров - в корзине лежат предложения, привязанные к товарам:
			foreach ($items as $index => $item) {
				// данные элемента предложения:
				$offer = \tools\Product::fetchElementById($item['sizeId']);
				//d($offer);
				if ($offer) {
					$offerProps = $offer->GetProperties();
					$items[$index]['sizeValue'] = $offerProps['RAZMER']['VALUE'];
				}
				//d($offer->GetProperties());
				// данные родительского продукта:
				$product = NULL;
				$productData = \CCatalogSku::GetProductInfo($item['sizeId']);
				if ( $productData ) $product = \tools\Product::fetchByIdOrCode($productData['ID']);
				if ( $product ) $items[$index]['product'] = $product;
				else {
					// основной товар предложения не найден...
					unset($items[$index]);
				}
				//$product = \tools\Product::fetchById($productData['ID']);
			}
			/*$result = \Bitrix\Catalog\ProductTable::getList(array(
				'filter' => [
					'=ID' => $offerIds,
				],
				'select' => ['ID','AVAILABLE','QUANTITY','NAME'=>'IBLOCK_ELEMENT.NAME','CODE'=>'IBLOCK_ELEMENT.CODE'],
			));
			if ($product = $result->fetch()) {
				print_r($product);
			}*/

			//d($items);
			$this->json['user']['basket'] = array_values($items);
		}

		protected function initFavorites() {
			\Bitrix\Main\Loader::includeModule("catalog");

			$params = self::postParams();
			//d($params);
			$productIds = $params['favorites'];

			$favs = [];
			foreach ($productIds as $productId) {
				$product = \tools\Product::fetchByIdOrCode($productId);
				if ( !$product ) continue;
				$favs[] = $product;
			}

			$this->json['user']['favorites'] = $favs;
		}

		protected function initCoupon() {

            $arFilter = Array(
                'IBLOCK_ID' => \ConfigBase::ID_IBLOCK_PERSONAL_COUPON,
                'ACTIVE_DATE' => 'Y',
                'ACTIVE' => 'Y',
                'CODE' => \ConfigBase::PERSONAL_COUPON_CODE
            );

            $arSelect = Array(
                "ID",
                "NAME",
                "PROPERTY_COUPON"
            );

            $item = \CIBlockElement::GetList(['SORT' => 'ASC'], $arFilter, false, false, $arSelect);

            if ($item = $item->GetNext()) {
                if ($item["PROPERTY_COUPON_VALUE"]) {
                    $this->json['user']['coupon'] = Array(
                        "name" => $item["NAME"],
                        "value" => $item["PROPERTY_COUPON_VALUE"],
                    );
                }
            }
        }
	}
