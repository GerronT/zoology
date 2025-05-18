<template>
  <BaseModal :visible="visible" @close="emitClose">
    <div class="mx-auto p-12 bg-gray-100 rounded-lg shadow-md">
        <h2 class="text-xl font-bold mb-6">{{groupInfo}} - Edit Group</h2>
        <form @submit.prevent="updateGroup" class="group-form">
            <group-base-form :group="groupForm" @update:group="updateGroupForm" :filteredClassifications="filteredClassifications" :filteredLevels="filteredLevels"/>
        
            <div class="flex justify-between gap-2 mt-6">
              <button type="button" class="px-4 py-2 bg-red-500 enabled:hover:brightness-75 text-white rounded-md disabled:bg-red-300 disabled:cursor-not-allowed" @click="emitClose" :disabled="isSaving">
                Close
              </button>
              <button type="submit" class="px-4 py-2 bg-green-500 enabled:hover:brightness-75 text-white rounded-md disabled:bg-green-300 disabled:cursor-not-allowed" :disabled="isSaving">
                Save Changes
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
    const classificationRanks = computed(() => store.getters.getRanks(RankTypes.CLASSIFICATION));
    const levelRanks = computed(() => store.getters.getRanks(RankTypes.LEVEL));

    const emitClose = () => emit('close');

    const groupForm = reactive({
      name: '',
      classification_id: '',
      level_id: '',
      description: '',
      is_clade: false
    });

    watch(() => props.visible, (isOpen) => {
      // Populate form when modal is opened
      if (isOpen) {
        const group = props?.data?.group;
        if (!group) return;
        groupForm.name = group.name;
        groupForm.classification_id = group.classification_id;
        groupForm.level_id = group.level_id;
        groupForm.description = group.description;
        groupForm.is_clade = !group.classification_id || !group.level_id;
      }
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

      if (group.animals.length > 0) {
        return props.classifications.filter(c => {
            return c.id == group.classification_id;
        });
      }

      const yngRnkAncestorEx = group.youngest_ranked_ancestor_exclusive;
      const bestRank = classificationRanks.value.get(yngRnkAncestorEx?.classification_id) ?? 1;
      
      const bstRnkDescendantEx = group.best_ranked_descendant_exclusive;
      const worseRank = classificationRanks.value.get(bstRnkDescendantEx?.classification_id) ?? classificationRanks.value.size;

      return props.classifications.filter(c => {
          const classRank = classificationRanks.value.get(c.id);
          return classRank >= bestRank && classRank <= worseRank;
      });
    }

    const filteredLevels = (classificationId = '') => {
      const group = props?.data?.group;
      if (!group) return props.levels;

      classificationId = classificationId || groupForm.classification_id;

      let filtered = props.levels;

      const yngRnkAncestorEx = group.youngest_ranked_ancestor_exclusive;
      const bestRank = levelRanks.value.get(yngRnkAncestorEx?.level_id) ?? 1;
      if (yngRnkAncestorEx?.classification_id == classificationId) {
        filtered = filtered.filter(l => {
            return levelRanks.value.get(l.id) > bestRank;
        });
      }
      
      const bstRnkDescendantEx = group.best_ranked_descendant_exclusive;
      const worseRank = levelRanks.value.get(bstRnkDescendantEx?.level_id) ?? classificationRanks.value.size;
      if (bstRnkDescendantEx?.classification_id == classificationId) {
        filtered = filtered.filter(l => {
            return levelRanks.value.get(l.id) < worseRank;
        });
      }

      return filtered;
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
    const updateGroup = async () => {
      isSaving.value = true;
      try {
        const group_id = props?.data?.group.id
        await axios.post(`/groups/${group_id}/update`, {
          ...groupForm
        });
        alert('Group saved!');
        emit('recordUpdate', {type: 'edit', group_id: group_id, only: [1]});
        emitClose();
      } catch (e) {
        alert('Error updating group');
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
      updateGroup,
    };
  },
};
</script>