<?php
	class OnUserUpdate  {
		protected static $userId;
		protected static $skipHandlers = false;

		const ENUM_IS_MODERATED_YES = "Да";

		public static function before(&$arFields) {
			if ( self::$skipHandlers ) return;

			self::$userId = $arFields["ID"];
			//AddMessage2Log("OnUserUpdate.before: ".self::$userId);

			/*if ( $arFields['UF_PHOTO'] ) $photo = CFile::GetPath($arFields['UF_PHOTO']);
			if ( $arFields['UF_PHOTO_UNMODERATED'] ) $photoUnmoderated = CFile::GetPath($arFields['UF_PHOTO_UNMODERATED']);
			//r($arFields);
			r($photo);
			d($photoUnmoderated);*/
		}

		public static function after(&$arFields) {
			if ( self::$skipHandlers ) return;
			//AddMessage2Log("OnUserUpdate.after: ".self::$userId);

			//d($arFields);

			// если прошли модерацию - переносим фотку PHOTO_UNMODERATED в PHOTO

			// промодерирован?
			if ( $arFields['UF_MODERATED'] ) {
				$rsUser = CUser::GetByID($arFields['ID']);
				if ($arUser = $rsUser->Fetch()) {

					// есть новая фотка?
					$photoUnmoderated = CFile::GetPath($arUser['UF_PHOTO_UNMODERATED']);
					if ( $photoUnmoderated ) {
						$user = new CUser;
						$fields = [
							// копируем в текущее новое фото
							"UF_PHOTO" => CFile::MakeFileArray($photoUnmoderated),
							// новое фото - удаляем
							"UF_PHOTO_UNMODERATED" => ['del' => 'Y']
						];

						// важно!! этот вызов снова вызывает хэндлеры и подвешивает вселенную, поэтому помечаем, что этого делать не нужно!
						self::$skipHandlers = true;
						$user->Update($arFields['ID'], $fields);
					}
				}
			}
		}
	}

	AddEventHandler('main', 'OnBeforeUserUpdate', ["OnUserUpdate", "before"]);
	AddEventHandler('main', 'OnAfterUserUpdate', ["OnUserUpdate", "after"]);
