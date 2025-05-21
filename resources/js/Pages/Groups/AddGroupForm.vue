<template>
  <div class="mx-auto p-12 bg-gray-100 rounded-lg shadow-md" :class="!isModal ? 'max-w-lg mt-8' : ''">
      <h2 class="text-xl font-bold mb-6">{{groupInfo}} - Add Child Group</h2>
      <form @submit.prevent="createChildGroup" class="group-form">
          <group-base-form :group="groupForm" @update:group="updateGroupForm" :filteredClassifications="filteredClassifications" :filteredLevels="filteredLevels"/>
      
          <div class="flex justify-between gap-2 mt-6">
            <button v-if="isModal" type="button" class="px-4 py-2 bg-red-500 enabled:hover:brightness-75 text-white rounded-md disabled:bg-red-300 disabled:cursor-not-allowed" @click="emitClose" :disabled="isSaving">
              Close
            </button>
            <button type="submit" class="px-4 py-2 bg-green-500 enabled:hover:brightness-75 text-white rounded-md disabled:bg-green-300 disabled:cursor-not-allowed" :disabled="isSaving">
              Add Group
            </button>
          </div>
      </form>
  </div>
</template>

<script>
import { reactive, computed, watch, ref, onMounted } from 'vue';
import axios from 'axios';
import GroupBaseForm from "../../Components/Groups/GroupBaseForm.vue";
import { RankTypes } from '@/constants/rankTypes';
import { useStore } from 'vuex';

export default {
  props: {
    parentGroup: Object,
    classifications: Array,
    levels: Array,
    isModal: {
      type: Boolean,
      default: false
    }
  },
  components: {
    GroupBaseForm
  },
  emits: ['recordUpdate', 'close'],
  setup(props, {emit}) {
    const store = useStore();
    const levelRanks = computed(() => store.getters.getRanks(RankTypes.LEVEL));

    const parentGroup = ref(props.isModal ? {...props.parentGroup} : {...props.parentGroup.data});

    const emitClose = () => emit('close');

    const defaultGroupForm = {
      name: '',
      classification_id: '',
      level_id: '',
      description: '',
      is_clade: false
    };
    const groupForm = reactive({...defaultGroupForm});

    onMounted(() => {
      Object.assign(groupForm, defaultGroupForm);
    });

    const groupInfo = computed(() => {
      const group = parentGroup.value;
      if (!group) return "";
      const classLevel = group?.classification_id && group?.level_id ? `${group.classification}/${group.level}` : "Unranked";
      return `${group?.name ?? "N/A"} (${classLevel})`;
    });

    const filteredClassifications = () => {
      const group = parentGroup.value;
      if (!group) return props.classifications;

      const highest_valid_cid = group.isRanked ? group.classification_id : group.yra_classification_id;
      const highest_valid_lid = group.isRanked ? group.level_id : group.yra_level_id;
 
      const nextClassRankId = store.getters.getNextRankedId(RankTypes.CLASSIFICATION, highest_valid_cid);

      return props.classifications.filter(c => {
          if (nextClassRankId && nextClassRankId == c.id) return true;
          if (c.id === highest_valid_cid && !store.getters.isLastRanked(RankTypes.LEVEL, highest_valid_lid)) return true;
          return false;
      });
    }

    const filteredLevels = (classificationId = '') => {
      const group = parentGroup.value;
      if (!group) return props.levels;

      classificationId = classificationId || groupForm.classification_id;

      const highest_valid_cid = group.isRanked ? group.classification_id : group.yra_classification_id;
      const highest_valid_lid = group.isRanked ? group.level_id : group.yra_level_id;

      if (highest_valid_cid == classificationId) {
        return props.levels.filter(l => {
            return levelRanks.value.get(l.id) > levelRanks.value.get(highest_valid_lid);
        });
      }

      return props.levels;
    }

    // Reset level_id if it's invalid for the selected classification_id
    watch(() => groupForm.classification_id, (newVal, oldVal) => {
      if (newVal !== oldVal) {
        const levels = filteredLevels(newVal);
        const isCurrentLevelStillValid = levels.some(l => l.id === groupForm.level_id);
        if (!isCurrentLevelStillValid) {
            groupForm.level_id = '';
        }
      }
    });

    const updateGroupForm = (key, value) => {
      groupForm[key] = value;
    };

    const isSaving = ref(false);
    const createChildGroup = async () => {
      isSaving.value = true;
      try {
        const group_id = parentGroup.value.id;
        await axios.post(`/groups`, {
          ...groupForm, parent_group_id: group_id
        });
        alert('Child group added!');
        if (props.isModal) {
          emit('recordUpdate', {type: 'add', group_id: group_id, forceOpen: true});
          emitClose();
        }
      } catch (e) {
        alert('Error creating child group');
      } finally {
        isSaving.value = false;
      }
    };
      
    return {
      emitClose,
      groupForm,
      groupInfo,
      filteredClassifications,
      filteredLevels,
      updateGroupForm,
      isSaving,
      createChildGroup,
    };
  },
};
</script>