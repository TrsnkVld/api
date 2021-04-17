import Vue from 'vue';
// import router from '../router/index';
import config from '@/config';

const mutations = {
  setResponse: (state, payload) => {
      if (!state.response) state.response = {};
      if (!payload) state.response = {};
      for (const actionName in payload) {
          Vue.set(state.response, actionName, payload[actionName]);

          //let actionData = state.response[actionName];
          // set session:
          //if ( actionData.user ) mutations.setSession(state, actionData);
      }
  },

  setPage: (state, payload) => {
      if (payload.page) {
          for (const actionName in payload.page) {
              // внимание, такой подход не реактивится:
              //state.page[key] = payload.page[key];
              // поэтому явным образом вызываем Vue.set():
              Vue.set(state.page, actionName, payload.page[actionName]);
              Vue.set(state, "currentPage", payload.page[actionName]);
          }
      }
      if (payload.common) {
          for (const key in payload.common) {
              // внимание, такой подход не реактивится:
              //state.page[key] = payload.page[key];
              // поэтому явным образом вызываем Vue.set():
              Vue.set(state.common, key, payload.common[key]);
          }
      }
      if (payload.user) {
          if ( payload.user.sessid ) {
              Cookie.set(Config.STORAGE_BITRIX_SESSID, payload.user.sessid);
              sessionStorage.setItem(Config.STORAGE_BITRIX_SESSID, payload.user.sessid);
          }
      }

      /*let currentPage = '';
      // в state есть routeTo - открываемая страница
      if (state.routeTo.meta.api)
          currentPage = state.routeTo.meta.api;
      else {
          currentPage = state.routeTo.name;
          currentPage = currentPage.charAt(0).toUpperCase() + currentPage.slice(1);
      }

      if ( state.page[currentPage] ) {
          headful({
              title: state.page[currentPage].title || '',
              keywords: state.page[currentPage].keywords || '',
              description: state.page[currentPage].description || ''
          });
      }*/
  },
};

export default mutations;
