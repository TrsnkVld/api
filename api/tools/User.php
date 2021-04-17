<?php

	namespace tools;

	class User {

		/**
		 * Возвращает пользователя с указанным id.
		 * @param $id
		 * @return mixed|null
		 */
		public static function byId($id) {
			$arFilter = [
				"ACTIVE" => 'Y',
				"ID" => $id
			];
			$rsUsers = \CUser::GetList(
				$by = array(), // поле для сортировки
				($order = "asc"),// сортировка вверх или вниз
				$arFilter,
				self::selectedFields());
			$arUser = $rsUsers->Fetch();
			if (!$arUser) return NULL;
			return $arUser = self::processAfterFetch($arUser);
		}

		/**
		 * Возвращает пользователя с указанным email.
		 * @param $email
		 * @param bool $withInactive - вернуть не только активных пользователей
		 * @return mixed|null
		 */
		public static function byEmail($email, $withInactive = false) {
			$arFilter = [
				"=EMAIL" => $email
			];
			if (!$withInactive) $arFilter['ACTIVE'] = 'Y';
			$rsUsers = \CUser::GetList(
				$by = array(), // поле для сортировки
				($order = "asc"),// сортировка вверх или вниз
				$arFilter,
				self::selectedFields());
			$arUser = $rsUsers->Fetch();
			if (!$arUser) return NULL;
			return $arUser = self::processAfterFetch($arUser);
		}

		/**
		 * Делает поля массива пользователя более удобными для использования.
		 * @param $item
		 * @return mixed
		 */
		public static function processAfterFetch($item) {
			//global $USER;

			/*$item['PERSONAL_PHOTO'] = CFile::GetPath($item['UF_PHOTO']);
			//$item['PERSONAL_PHOTO'] = (CFile::GetPath($item['PERSONAL_PHOTO']) ?: Config::DEFAULT_USER_PHOTO);
			if ( $item['UF_HIDE_PHONE'] ) {
				$item['PERSONAL_MOBILE'] = NULL;
			}
			else {
				$item['PERSONAL_MOBILE'] = $item['UF_MOBILE'] ?: $item['PERSONAL_MOBILE'];
			}
			$item['UF_PHONE'] = $item['UF_PHONE'] ? $item['UF_PHONE'] : $item['WORK_PHONE'];
			//$item['UF_STATUS'] = $item['UF_STATUS'] ?: UserEntity::STATUSES[0];

			// непромодерированное фото - только для текущего пользователя:
			if ( $item['ID'] == $USER->GetID() ) {
				$item['UF_PHOTO_UNMODERATED'] = CFile::GetPath($item['UF_PHOTO_UNMODERATED']);
			}
			else $item['UF_PHOTO_UNMODERATED'] = NULL;

			$item['EMAIL'] = $item['UF_EMAIL'] ?: $item['EMAIL'];
			$item['PERSONAL_BIRTHDAY'] = $item['UF_BIRTHDAY'] ?: $item['PERSONAL_BIRTHDAY'];

			// оверрайд локации:
			$location = self::locationOf($item);
			$item['UF_LOCATION_ID'] = intval($location['id']);

            // Удаляем телефон и день рождения Баранов В.В.
            if ($item['ID'] == self::ID_CEO) {
                $item['PERSONAL_BIRTHDAY'] = '';
                $item['PERSONAL_PHONE'] = '';
                $item['PERSONAL_MOBILE'] = '';
                $item['PERSONAL_PAGER'] = '';

                $item['WORK_PHONE'] = '';
                $item['WORK_FAX'] = '';
                $item['WORK_PAGER'] = '';

                $item['UF_BIRTHDAY'] = '';
                $item['UF_PHONE'] = '';
                $item['UF_MOBILE'] = '';
            }*/

			return $item;
		}

		/**
		 * Формирует ФИО пользователя.
		 * @param $user
		 * @return string
		 */
		public static function fio($user) {
			return $user['LAST_NAME'] . " " . $user['NAME'] . " " . $user['SECOND_NAME'];
		}

		/**
		 * Используйте этот метод для установки полей для фетчей.
		 * @return array
		 */
		public static function selectedFields() {
			return ["SELECT" => ["UF_*"]];
		}

		/**
		 * // TODO: удалить, не используется
		 * @deprecated
		 * @param $params
		 * @return array
		 */
		public function Update($params) {
			if ($params['type'] === 'form') {
				$user = new \CUser;
				$fields = Array(
					"UF_EMAIL" => $params['data']['UF_EMAIL'],
					"UF_PHONE" => $params['data']['UF_PHONE'],
					"UF_MOBILE" => $params['data']['UF_MOBILE'],
					"UF_CABINET" => $params['data']['UF_CABINET'],
					"UF_BIRTHDAY" => $params['data']['UF_BIRTHDAY'],
					//"UF_STATUS" => $params['data']['UF_STATUS'],
				);
				$res = $user->Update($params['ID'], $fields);

				if ($res) {
					return [
						'success' => true
					];
				} else {
					return [
						'success' => false,
						'error' => $user->LAST_ERROR
					];
				}
			} elseif ($params['type'] === 'photo') {
				$data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $params['data']));
				$path_tmp = $_SERVER["DOCUMENT_ROOT"] . '/upload/image' . $params['ID'] . '.png';

				$resPutFile = file_put_contents($path_tmp, $data);

				$fields = Array(
					"UF_PHOTO" => \CFile::MakeFileArray($path_tmp),
				);

				$user = new \CUser;
				$res = $user->Update($params['ID'], $fields);

				if ($res) {
					return [
						'$fields' => $fields,
						'$resPutFile' => $resPutFile,
						'success' => true
					];
				} else {
					return [
						'success' => false,
						'error' => $user->LAST_ERROR
					];
				}
			}
		}

		public static function registerAndLogin($params) {
			define("AUTH_BLOCK", "AUTH");

			$userProps = [
				"EMAIL" => $params['email'],
				"PHONE" => $params['phone'],
				"NAME" => $params['name'],
				//"NAME_LAST" => $params['nameLast'],
				"ADDRESS" => $params['address'],
			];
			$userData = self::generateUserData($userProps);

			$user = new \CUser;
			$arAuthResult = $user->Add(array(
				'LOGIN' => $userData['NEW_LOGIN'],
				'NAME' => $userData['NEW_NAME'],
				//'LAST_NAME' => $userData['NEW_LAST_NAME'],
				'PASSWORD' => $userData['NEW_PASSWORD'],
				'CONFIRM_PASSWORD' => $userData['NEW_PASSWORD_CONFIRM'],
				'EMAIL' => $userData['NEW_EMAIL'],
				'GROUP_ID' => $userData['GROUP_ID'],
				'ACTIVE' => 'Y',
				'LID' => SITE_ID,
				'PERSONAL_PHONE' => isset($userProps['PHONE']) ? $userProps['PHONE'] : '',
				//'PERSONAL_ZIP' => isset($userProps['ZIP']) ? $userProps['ZIP'] : '',
				'PERSONAL_STREET' => isset($userProps['ADDRESS']) ? $userProps['ADDRESS'] : ''
			));

			$userId = intval($arAuthResult);
			if ($userId <= 0) {
				throw new \Exception( ((strlen($user->LAST_ERROR) > 0) ? ': ' . $user->LAST_ERROR : ''), AUTH_BLOCK);
			}

			global $USER;
			$USER->Authorize($userId);
			if ($USER->IsAuthorized()) {
				/*if ($params['SEND_NEW_USER_NOTIFY'] == 'Y')
					\CUser::SendUserInfo($USER->GetID(), SITE_ID, \Loc::getMessage('INFO_REQ'), true);*/
			} else
				throw new \Exception("Error", AUTH_BLOCK);

			$returnUser = array();
			$returnUser["PASSWORD"] = $userData['NEW_PASSWORD'];
			$returnUser["ID"] = $userId;

			return $returnUser;
		}

		protected static function generateUserData($userProps = array()) {
			global $USER;// = self::$USER;

			$userEmail = (is_array($userProps) && $userProps['EMAIL'] != '') ? $userProps['EMAIL'] : '';
			$newLogin = $userEmail;
			$newEmail = $userEmail;

			//$payerName = (is_array($userProps) && $userProps['PAYER'] != '') ? $userProps['PAYER'] : '';

			if ($userEmail == '') {
				$newEmail = false;
				if (is_array($userProps) && $userProps['PHONE'] != '')
					$newLogin = trim($userProps['PHONE']);
				else
					$newLogin = "user_" . \randString(7);
			}

			$newName = $userProps["NAME"];
			//$newLastName = $userProps["NAME_LAST"];

			/*if (strlen($payerName) > 0) {
				$arNames = explode(" ", $payerName);
				$newName = $arNames[1];
				$newLastName = $arNames[0];
			}*/

			$pos = strpos($newLogin, "@");
			if ($pos !== false)
				$newLogin = substr($newLogin, 0, $pos);

			if (strlen($newLogin) > 47)
				$newLogin = substr($newLogin, 0, 47);

			if (strlen($newLogin) < 3)
				$newLogin .= "_";

			if (strlen($newLogin) < 3)
				$newLogin .= "_";

			$dbUserLogin = \CUser::GetByLogin($newLogin);
			if ($arUserLogin = $dbUserLogin->Fetch()) {
				$newLoginTmp = $newLogin;
				$uind = 0;
				do {
					$uind++;
					if ($uind == 10) {
						$newLogin = $userEmail;
						$newLoginTmp = $newLogin;
					} elseif ($uind > 10) {
						$newLogin = "buyer" . time() . \GetRandomCode(2);
						$newLoginTmp = $newLogin;
						break;
					} else {
						$newLoginTmp = $newLogin . $uind;
					}
					$dbUserLogin = \CUser::GetByLogin($newLoginTmp);
				} while ($arUserLogin = $dbUserLogin->Fetch());
				$newLogin = $newLoginTmp;
			}

			$def_group = \Bitrix\Main\Config\Option::get("main", "new_user_registration_def_group", "");
			if ($def_group != "") {
				$groupID = explode(",", $def_group);
				$arPolicy = $USER->GetGroupPolicy($groupID);
			} else {
				$arPolicy = $USER->GetGroupPolicy(array());
			}

			$password_min_length = intval($arPolicy["PASSWORD_LENGTH"]);
			if ($password_min_length <= 0)
				$password_min_length = 6;
			$password_chars = array(
				"abcdefghijklnmopqrstuvwxyz",
				"ABCDEFGHIJKLNMOPQRSTUVWXYZ",
				"0123456789",
			);
			if ($arPolicy["PASSWORD_PUNCTUATION"] === "Y")
				$password_chars[] = ",.<>/?;:'\"[]{}\|`~!@#\$%^&*()-_+=";
			$newPassword = $newPasswordConfirm = \randString($password_min_length + 2, $password_chars);

			return array(
				'NEW_EMAIL' => $newEmail,
				'NEW_LOGIN' => $newLogin,
				'NEW_NAME' => $newName,
				//'NEW_LAST_NAME' => $newLastName,
				'NEW_PASSWORD' => $newPassword,
				'NEW_PASSWORD_CONFIRM' => $newPasswordConfirm,
				'GROUP_ID' => $groupID
			);
		}
	}

?>
