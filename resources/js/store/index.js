// store/index.js
import { createStore } from 'vuex';

function convertToMap(data) {
  return new Map(Object.entries(data).map(([key, value]) => [Number(key), value]));
}

export default createStore({
  state: {
    classificationRanks: {},
    levelRanks: {},
  },
  mutations: {
    setClassificationRanks(state, ranks) {
      state.classificationRanks = ranks;
    },
    setLevelRanks(state, ranks) {
      state.levelRanks = ranks;
    },
  },
  actions: {
    fetchRanks({ commit }) {
      axios.get('/api/ranks').then((response) => {
        commit('setClassificationRanks', response.data.classificationRanks);
        commit('setLevelRanks', response.data.levelRanks);
      });
    },
  },
  getters: {
    getClassificationRanks(state) {
        return convertToMap(state.classificationRanks);
    },
    getLevelRanks(state) {
        return convertToMap(state.levelRanks);
    },
  },
});
