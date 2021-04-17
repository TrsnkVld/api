const getters = {
  page(state) {
    return state.page;
  },

  common(state) {
    return state.common;
  },

  user(state) {
    return state.account.user;
  },
};

export default getters;
