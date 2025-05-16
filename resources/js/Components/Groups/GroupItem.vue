<template>
    <div>
        <!-- Toggle -->
        <div class="flex items-center justify-between mb-4">
            <label class="font-semibold">
            {{ group.useNewGroup ? 'Create new' : 'Select existing' }} group
            </label>
            <button v-if="isGroupEditable()" type="button" class="text-sm text-blue-600 hover:underline" @click="switchGroupSelectNew">
            {{ group.useNewGroup ? '🔁 Switch to existing group' : '➕ Create new group' }}
            </button>
        </div>

        <!-- Existing Group Dropdown -->
        <div v-if="!group.useNewGroup" class="mb-6">
            <vue-select :v-model="group.id" :options="filteredSearchGroups()" @search="(query) => emit('onSearchGroup', query)" :get-option-label="getGroupLabel" :disabled="!isGroupEditable()" placeholder="Search Group..." class="child-group-select" @update:modelValue="(selectedGroup) => updateGroupId(selectedGroup)">
            <template #no-options>
                <span v-if="index == 0">
                  <span v-if="groupSearchQuery[index]?.length > 0">🔍 No groups found. Try a different search.</span>
                  <span v-else>⌨️ Start searching for existing groups...</span>
                </span>
                <span v-else>🔍 No other subgroups found</span>
            </template>
            </vue-select>
        </div>

        <!-- New Group Form -->
        <div v-if="group.useNewGroup">
            <group-base-form 
              :group="group"
              @update:group="updateGroupField"
              :isGroupEditable="isGroupEditable" 
              :filteredClassifications="filteredClassifications"
              :filteredLevels="filteredLevels"/>
        </div>

        <!-- Remove button -->
        <div class="text-right mt-4" v-if="index > 0 && isGroupEditable()">
            <button type="button" class="px-4 py-2 bg-red-500 text-white rounded-md" @click="() => emit('removeGroup')">
              Remove
            </button>
        </div>
    </div>
</template>

<script>
import VueSelect from 'vue3-select'; // Import vue3-select for Vue 3
import 'vue3-select/dist/vue3-select.css';
import GroupBaseForm from './GroupBaseForm.vue';

export default {
  props: {
    group: Object,
    index: Number,
    groupSearchQuery: Array,
    isGroupEditable: Function,
    filteredSearchGroups: Function,
    filteredClassifications: Function,
    filteredLevels: Function,
    getClassificationById: Function,
    getLevelById: Function,
  },
  components: {
    VueSelect,
    GroupBaseForm,
  },
  emits: ['removeGroup', 'onSearchGroup', 'removeGroupFromPreselected', 'addGroupToPreselected', 'update:group'],
  setup(props, { emit }) {
    const updateGroupField = (key, value) => {
      emit('update:group', { key, value });
    };

    const switchGroupSelectNew = () => {
      updateGroupField('useNewGroup', !props.group.useNewGroup);
      if (props.group.useNewGroup) {
        emit('removeGroupFromPreselected', props.group);
        updateGroupField('id', null);
      } else {
        updateGroupField('name', '');
        updateGroupField('classification_id', null);
        updateGroupField('level_id', 5);
        updateGroupField('description', '');
        updateGroupField('is_clade', false);
      }
    }

    const getGroupLabel = (groupItem) => {
      const classification = props.getClassificationById(groupItem.classification_id)
      const level = props.getLevelById(groupItem.level_id);

      return `${groupItem.name} - ${classification && level ? classification.name + ' - ' + level.name : '(Unranked Clade)'}`;
    };

    const updateGroupId = (selectedGroup) => {
      emit('removeGroupFromPreselected', props.group);
      emit('addGroupToPreselected', selectedGroup);
      updateGroupField('id', selectedGroup?.id || null);
    };

    return {
      emit,
      updateGroupField,
      switchGroupSelectNew,
      updateGroupId,
      getGroupLabel
    };
  },
};
</script>

