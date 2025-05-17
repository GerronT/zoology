// store/index.js
import { createStore } from 'vuex';
import { RankTypes } from '../constants/rankTypes';

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
    getRanks: (state) => (type) => {
      switch (type) {
        case RankTypes.CLASSIFICATION:
          return convertToMap(state.classificationRanks);
        case RankTypes.LEVEL:
          return convertToMap(state.levelRanks);
        default:
          return new Map();
      }
    },

    getFirstRankedId: (state, getters) => (type) => {
      const rankings = getters.getRanks(type);

      for (const [id, rank] of rankings.entries()) {
        if (rank === 1) return id;
      }

      return null;
    },

    getLastRankedId: (state, getters) => (type) => {
      const rankings = getters.getRanks(type);

      for (const [id, rank] of rankings.entries()) {
        if (rank === rankings.size) return id;
      }

      return null;
    },

    isLastRanked: (state, getters) => (type, id) => {
      const rankings = getters.getRanks(type);
      const maxRank = Math.max(...rankings.values());

      return rankings.get(id) === maxRank;
    },

    getNextRankedId: (state, getters) => (type, id) => {
      const rankings = getters.getRanks(type);
      const currentRank = rankings.get(id);

      if (!currentRank) return null;

      for (const [otherId, rank] of rankings.entries()) {
        if (rank === currentRank + 1) {
          return otherId;
        }
      }

      return null;
    }
  },
});
