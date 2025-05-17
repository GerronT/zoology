<template>
  <BaseModal :visible="visible" @close="emitClose">
    <div class="mx-auto p-12 bg-gray-100 rounded-lg shadow-md">
        <h2 class="text-xl font-bold mb-6">{{headerTitle}}</h2>
        <form @submit.prevent="groupHandler" class="group-form">
            <group-base-form :group="groupForm" @update:group="updateGroupForm" :filteredClassifications="filteredClassifications" :filteredLevels="filteredLevels"/>
        
            <div class="flex justify-between gap-2 mt-6">
              <button type="button" class="px-4 py-2 bg-red-500 hover:brightness-75 text-white rounded-md disabled:bg-red-300 disabled:cursor-not-allowed" @click="emitClose" :disabled="isSaving">
                Close
              </button>
              <button type="submit" class="px-4 py-2 bg-green-500 hover:brightness-75 text-white rounded-md disabled:bg-green-300 disabled:cursor-not-allowed" :disabled="isSaving">
                {{buttonTitle}}
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
  emits: ['close'],
  setup(props, {emit}) {
    
  const store = useStore();

    const classificationRanks = computed(() => store.getters.getRanks(RankTypes.CLASSIFICATION));
    const levelRanks = computed(() => store.getters.getRanks(RankTypes.LEVEL));

    const defaultGroupForm = {
      name: '',
      classification_id: '',
      level_id: '',
      description: '',
      is_clade: false
    };
    
    const groupForm = reactive({...defaultGroupForm});

    const createChildGroup = async () => {
      isSaving.value = true;
      try {
        await axios.post('/groups/create-child', {
          ...groupForm, parent_group_id: props?.data?.group?.id
        });
        alert('Child group added!');
        location.reload();
      } catch (e) {
        alert('Error creating child group');
      }
      isSaving.value = false;
      emitClose();
    };

    const updateGroup = async () => {
      isSaving.value = true;
      try {
        const id = props?.data?.group.id
        await axios.post(`/groups/${id}/update`, {
          ...groupForm
        });
        alert('Group saved!');
        location.reload();
      } catch (e) {
        alert('Error updating group');
      }
      isSaving.value = false;
      emitClose();
    };
    
    const headerTitle = ref('');
    const buttonTitle = ref('');
    const groupHandler = ref(createChildGroup);
    const isSaving = ref(false);

    watch(() => props.visible, (newVal) => {
        if (newVal) {
          initializeModal();
        }
      }
    );

    const initializeModal = () => {
        if (props.data) {
          const type = props.data?.type;
          const group = props.data?.group;

          let groupStartingTitle = "";
          const classLevel = group?.classification_id && group?.level_id ? group?.classification + "/" + group?.level : "Unranked";
          groupStartingTitle = (group?.name ?? "N/A") + " (" + classLevel  + ")";

          switch (type) {
            case 'edit':
              headerTitle.value = groupStartingTitle + " - Edit Group";
              buttonTitle.value = "Save Changes"
              groupHandler.value = updateGroup;
              groupForm.name = group.name;
              groupForm.classification_id = group.classification_id;
              groupForm.level_id = group.level_id;
              groupForm.description = group.description;
              groupForm.is_clade = !group.classification_id || !group.level_id;
              break;
            case 'add':
              headerTitle.value = groupStartingTitle + " - Add Child Group";
              buttonTitle.value = "Add Group"
              groupHandler.value = createChildGroup;
              Object.assign(groupForm, defaultGroupForm);
              break;
            default:
          }
        }
    };

    const filteredClassifications = () => {
      if (!props.data) return props.classifications;
      
      const type = props.data?.type;
      const group = props.data?.group;

      switch (type) {
        case 'edit':
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
        case 'add':
        default:
          const yngRnkAncestor = group.youngest_ranked_ancestor;

          if (yngRnkAncestor) {
            const nextClassRankId = store.getters.getNextRankedId(RankTypes.CLASSIFICATION, yngRnkAncestor.classification_id);

            return props.classifications.filter(c => {
                if (nextClassRankId && nextClassRankId == c.id) return true;
                if (c.id === yngRnkAncestor.classification_id && !store.getters.isLastRanked(RankTypes.LEVEL, yngRnkAncestor.level_id)) return true;
                return false;
            });
        }
      }

      return props.classifications;
    }

    const filteredLevels = (classificationId = '') => {
      if (!props.data) return props.levels;

      const type = props.data?.type;
      const group = props.data?.group;

      classificationId = classificationId !== '' ? classificationId : groupForm.classification_id;

      switch (type) {
        case 'edit':
          const yngRnkAncestorEx = group.youngest_ranked_ancestor_exclusive;
          const bestRank = levelRanks.value.get(yngRnkAncestorEx?.level_id) ?? 1;

          let filtered = props.levels;
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
        case 'add':
          
        default:
          const yngRnkAncestor = group.youngest_ranked_ancestor;

          if (yngRnkAncestor?.classification_id == classificationId) {
            return props.levels.filter(l => {
                return levelRanks.value.get(l.id) > levelRanks.value.get(yngRnkAncestor.level_id);
            });
          }
      }

      return props.levels;
    }

    const emitClose = () => emit('close');

    const updateGroupForm = (key, value) => {
      groupForm[key] = value;
    };

    watch(() => groupForm.classification_id, (newVal, oldVal) => {
      if (newVal !== oldVal) {
        const levels = filteredLevels(newVal);
        const isCurrentLevelStillValid = levels.some(l => l.id === groupForm.level_id);
        if (!isCurrentLevelStillValid) {
            groupForm.level_id = '';
        }
      }
    });
    
    return {
        headerTitle,
        buttonTitle,
        emitClose,
        filteredClassifications,
        filteredLevels,
        groupForm,
        updateGroupForm,
        isSaving,
        groupHandler
    };
  },
};
</script>