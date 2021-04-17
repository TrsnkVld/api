let state = {
	// основной обхект ответа от БЕ
	response: {},
	// текущая сессия
	user: {
		user: '',
		auth: false,
		codeToken: ''
	},
	// данные из БЕ
	common: {},          // общие для всех страниц
	page: {},        // индивидуальные для каждой страницы
	currentPage: {},   // текущая страница

	isBoatCountChange: false,
	isPreloaderShowing: true,
	isPreloaderLogoShowing: true,
	isMapLoading: false,
	isMapLoaded: false,
	isCardOpened: false,
	currentBoat: '',

	tariffsFilter: {
		season: '',
		days: '',
		times: '',
	},
	headerForm: {
		name: '',
		phone: '',
	},
	orderForm: {
		date: '',
		time: '',
		adults: {},
		children: {},
		foreigners: {},
		name: '',
		phone: '',
		email: '',
		text: '',
		сonfirmOptions: 'Телефонный звонок от менеджера компании',
	},
	shipOrderForm: {
		shipId: '',
		date: '',
		time: '',
		name: '',
		phone: '',
		email: '',
		text: '',
		сonfirmOptions: 'Телефонный звонок от менеджера компании',
	},
	feedbackForm: {
		email: '',
		msg: '',
		name: '',
	},
	faqForm: {
		email: '',
		msg: '',
		name: '',
	},
	serviceForm: {
		date: '',
		time: '',
		name: '',
		email: '',
	}
};

export default state;
