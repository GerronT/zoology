<template>
  <BaseModal :visible="visible" @close="emitClose">
    <div class="mx-auto p-12 bg-gray-100 rounded-lg shadow-md">
        <h2 class="text-xl font-bold mb-6">{{groupInfo}} - Add Child Group</h2>
        <form @submit.prevent="createChildGroup" class="group-form">
            <group-base-form :group="groupForm" @update:group="updateGroupForm" :filteredClassifications="filteredClassifications" :filteredLevels="filteredLevels"/>
        
            <div class="flex justify-between gap-2 mt-6">
              <button type="button" class="px-4 py-2 bg-red-500 enabled:hover:brightness-75 text-white rounded-md disabled:bg-red-300 disabled:cursor-not-allowed" @click="emitClose" :disabled="isSaving">
                Close
              </button>
              <button type="submit" class="px-4 py-2 bg-green-500 enabled:hover:brightness-75 text-white rounded-md disabled:bg-green-300 disabled:cursor-not-allowed" :disabled="isSaving">
                Add Group
              </button>
            </div>
        </form>
    </div>
  </BaseModal>
</template>

<script>
import { reactive, computed, watch, ref } from 'vue';
import axios from 'axios';
import BaseModal from "./BaseModal.vue";
import GroupBaseForm from "../Groups/GroupBaseForm.vue";
import { RankTypes } from '@/constants/rankTypes';
import { useStore } from 'vuex';

export default {
  props: {
    data: Object,
    visible: Boolean,
    classifications: Array,
    levels: Array
  },
  components: {
    GroupBaseForm,
    BaseModal
  },
  emits: ['close', 'recordUpdate'],
  setup(props, {emit}) {
    const store = useStore();
    const levelRanks = computed(() => store.getters.getRanks(RankTypes.LEVEL));

    const emitClose = () => emit('close');

    const defaultGroupForm = {
      name: '',
      classification_id: '',
      level_id: '',
      description: '',
      is_clade: false
    };
    const groupForm = reactive({...defaultGroupForm});

    watch(() => props.visible, (isOpen) => {
      // Reset form when modal is opened
      if (isOpen) Object.assign(groupForm, defaultGroupForm);
    });

    const groupInfo = computed(() => {
      const group = props?.data?.group;
      if (!group) return "";
      const classLevel = group?.classification_id && group?.level_id ? `${group.classification}/${group.level}` : "Unranked";
      return `${group?.name ?? "N/A"} (${classLevel})`;
    });

    const filteredClassifications = () => {
      const group = props?.data?.group;
      if (!group) return props.classifications;

      const yngRnkAncestor = group?.youngest_ranked_ancestor;
      if (yngRnkAncestor) {
        const nextClassRankId = store.getters.getNextRankedId(RankTypes.CLASSIFICATION, yngRnkAncestor.classification_id);

        return props.classifications.filter(c => {
            if (nextClassRankId && nextClassRankId == c.id) return true;
            if (c.id === yngRnkAncestor.classification_id && !store.getters.isLastRanked(RankTypes.LEVEL, yngRnkAncestor.level_id)) return true;
            return false;
        });
      }

      return props.classifications;
    }

    const filteredLevels = (classificationId = '') => {
      const group = props?.data?.group;
      if (!group) return props.levels;

      classificationId = classificationId || groupForm.classification_id;
      const yngRnkAncestor = group?.youngest_ranked_ancestor;
      if (yngRnkAncestor?.classification_id == classificationId) {
        return props.levels.filter(l => {
            return levelRanks.value.get(l.id) > levelRanks.value.get(yngRnkAncestor.level_id);
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
        const group_id = props?.data?.group?.id;
        await axios.post('/groups/create-child', {
          ...groupForm, parent_group_id: group_id
        });
        alert('Child group added!');
        emit('recordUpdate', {type: 'add', group_id: group_id, forceOpen: true});
        emitClose();
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