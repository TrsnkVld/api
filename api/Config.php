<?php

	class ConfigBase {
		const VERSION = "1";
		const PATH_VENDOR = "../vendor";
		const PATH_UPLOAD = "./upload";

		const COOKIE_AUTH = "auth";
		const API_PRIVATE_KEY = 'PlayNext-one-love';
		const IS_LIVE = true;

		const EMAIL_FROM = "support@playnext.ru";

		const ID_ADMIN = 1;

		const IBLOCK_ID_SHIP = 2;
		const IBLOCK_ID_SHIP_TP = 5;
		const IBLOCK_ID_PUBS = 1;
		const IBLOCK_ID_HOTS = 8;
		const IBLOCK_ID_ARTICLES = 6;
		const IBLOCK_ID_CONTENT = 9;
		const IBLOCK_ID_SPECIALIST = 10;
		const IBLOCK_ID_SOURCE = 14;
		const IBLOCK_ID_CLAIM = 11;
		const IBLOCK_ID_FAQ = 12;
		const IBLOCK_ID_TECH = 13;
		const IBLOCK_ID_EXCS = 3;
		const IBLOCK_ID_EXCS_TP = 7;

		const ID_ABOUT = 46;
		const ID_CLAIMS = 69;
		const ID_GALLERY = 47;
		const ID_TECH = 53;
		const ID_HOME = 65;
		const ID_HOTS = 66;
		const ID_SHIPS = 67;
		const ID_EX = 70;
		const ID_TARIFS = 68;
		const ID_CONFIRM = 253;
		const ID_SPECIALIST = 254;

		const IBLOCK_ID_CARDBOARD_TYPES = 5;
		const IBLOCK_CODE_CARDBOARD_TYPES = 'cardboard_types';
		const IBLOCK_CODE_MODELS = 'models';
		const IBLOCK_CODE_TEXTURES = 'textures';
		const IBLOCK_ID_TEXTURES = 7;
		const IBLOCK_CODE_CRAFT_TEXTURES = 'craft_textures';
		const IBLOCK_CODE_FULL_TEXTURES = 'full_textures';
		const IBLOCK_CODE_TAILS = 'tails';
		const IBLOCK_CODE_FILLINGS = 'fillings';
		const IBLOCK_ID_FILLINGS = 15;
		const IBLOCK_CODE_LAMINATION_TYPES = 'lamination_types';
		const IBLOCK_CODE_ORDERS = 'orders';
		const IBLOCK_ID_ORDERS = 13;
		const IBLOCK_CODE_TEXTS = 'texts';

		const WITH_CACHE = true;
		const CACHE_EXPIRY_SEC = 15 * 60;    // GET JSON-запросы кэшируются на это время силами браузера headers
		const CACHE_FILE_CACHE_EXPIRY_SEC = 8 * 60 * 60;    // GET JSON-запросы кэшируются на это время силами сервера
		const CACHE_FILE_CACHE_PREFIX = 'json.cache';    // GET JSON-запросы кэшируются на сервере с таким префиксом

        const DEFAULT_PHOTO_QUALITY = 70; // 0 - 100 сжатие картинки в % (чем меньше значение тем больше сжатие)
        const PORTFOLIO_CATEGORY_IMG_WIDTH = 1070; //max-width под размер модального окна
        const PORTFOLIO_CATEGORY_IMG_HEIGHT = 715;
        const PORTFOLIO_MODEL_IMG_WIDTH = 1070;// под размер модального окна
        const PORTFOLIO_MODEL_IMG_HEIGHT = 715;
        const REVIEWS_IMG_WIDTH = 400;
        const REVIEWS_IMG_HEIGHT = 500;
        const FAQ_IMG_WIDTH = 1200;
        const FAQ_IMG_HEIGHT = 700;

		const ID_PRICE_FILE_TYPE_DEFAULT = 34; // Файл цен содержит список цен в обычном формате
		const ID_PRICE_FILE_TYPE_TEXTURES = 35; // Файл цен содержит список цен с текстурами в строчках

        const SIDE_PRINT_SILK = 'Шелкография';
        const SIDE_PRINT_EMBOSSING = 'Тиснение';

        const ID_TEXTURE_TYPE_TEXTURE = 21;	// должно быть таким же как в /src/Config.js
        const ID_TEXTURE_TYPE_CRAFT = 22;	// должно быть таким же как в /src/Config.js
        const ID_TEXTURE_TYPE_FULL = 23;	// должно быть таким же как в /src/Config.js

		const LAMINATION_SOFT_TOUCH = "SOFT_TOUCH";	// должно быть таким же как в /src/Config.js

		const FULL_TEXTURE_PREVIEW_WIDTH = 200;
		const FULL_TEXTURE_PREVIEW_HEIGHT = 200;

        const ENCODE_KEY = "PlayNext one love";	// for non critical data encoding
        const ENCODE_METHOD = "aes-256-cbc";	// for non critical data encoding
	}

	if (isset($_SERVER['PLAYNEXT_ENV'])) {
		// PlayNext dev env:
		class Config extends ConfigBase {
			const IS_LIVE = false;
            const WITH_CACHE = false;
		}
	} else {
		// Live env:
		class Config extends ConfigBase {
			// TODO: глючит серверно кэширование - исправить
			const WITH_CACHE = false;
		}
	}

	ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_WARNING & ~E_DEPRECATED);
	ini_set('display_errors', Config::IS_LIVE ? 0 : 1);
	ini_set('display_startup_errors', Config::IS_LIVE ? 0 : 1);

	require_once($_SERVER["DOCUMENT_ROOT"] . "/../api/libs/globals.php");
?>
