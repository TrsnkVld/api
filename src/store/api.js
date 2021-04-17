import axios from "axios";
import Cookie from 'js-cookie';
import config from '@/config';
import {mergeObjects} from '@/utils/utils';
import 'axios-progress-bar/dist/nprogress.css';
import { loadProgressBar } from 'axios-progress-bar';

const axiosWithProgress = axios.create({withCredentials: true});
const axiosWithoutProgress = axios.create({withCredentials: true});  
loadProgressBar({ showSpinner: false },axiosWithProgress);

export function api(cmd, options) {
	// по умолчанию - GET-запрос
	options.method = options.method || 'GET';

	console.log('API '+options.method+' request \"' + cmd + '\": ', options);

	// с прогрессбаром или нет?
	let axios = null;
	if ( options.hidden ) axios = axiosWithoutProgress;
	else axios = axiosWithProgress;

	// добавляем токен авторизации
	//let authToken = Cookie.get(config.STORAGE_AUTH_TOKEN);
	//if ( !authToken )
	let authToken = localStorage.getItem(config.STORAGE_AUTH_TOKEN);
	//console.log("AUTH taken from cookie/storage: ",auth);
	if (authToken) axios.defaults.headers.common['Authorization'] = authToken;

	// добавляем Битрикс sessid
	let sessid = Cookie.get(config.STORAGE_BITRIX_SESSID);
	if ( !sessid ) sessid = sessionStorage.getItem(config.STORAGE_BITRIX_SESSID);
	if (sessid) {
		options.params = options.params || {};
		options.params.sessid = sessid;
	}

	// собираем данные для отправки (GET params и POST data)
	let params = null, data = null;
	if ( options.method === "GET" ) {
		// конкатинируем GET params & query:
		params = options.params || {};
		let query = options.query || {};
		params = mergeObjects(params, query);
	}
	else data = options.params;

	// определяем URL:
	let url = '';
	if (cmd.match(/^\/.+/)||cmd.match(/^http:.+/) ) {
		// кастомный URL
		url = cmd;
		console.log("Requesting URL: "+url);
	}
	else {
		// По умолчанию обращаемся к API
		url = (config.API_SERVER_URI?config.API_SERVER_URI:'') + '/api/' + cmd + '/';
	}

	// установка Accept
	let accepts = ["application/json"];
	if ( config.WITH_WEBP ) accepts.push("image/webp");
	axios.defaults.headers.common['Accept'] = accepts.join(", ");

	// TODO: кастомные заголовки:
	let headers = {};
	//let headers = {'Cache-Control': 'max-age=300'};
	/*if (typeof options.headers !== 'undefined' && Object.keys(options.headers).length > 0) {
        _.forEach(options.headers, (val, key) => headers[key] = val);
    }*/

	// погнали
	return axios({
		method: options.method,
		url,
		params,
		headers,
		data
	}).catch((error) => {
		// TODO: communication error handling
		console.error('API request error: ', error);
		return error;
	}).then((response) => {
		console.log('API Response: ', response);

		if (!response.data) throw 'No data in response object';
		// if(response.data.error) return router.push(<any>{name: 'Error'});

		if (response.data.error) {
			// API standard error
			console.warn('API error: ', response.data.error);
			throw response.data.error;
		}
		if (!response.data||!response.data.result) throw 'No result in response data object';

		return response.data.result;
	});
}

export default api;
