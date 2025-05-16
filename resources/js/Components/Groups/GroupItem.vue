<template>
    <div>
        <!-- Toggle -->
        <div class="flex items-center justify-between mb-4">
            <label class="font-semibold">
            {{ group.useNewGroup ? 'Create new' : 'Select existing' }} group
            </label>
            <button v-if="isGroupEditable(index)" type="button" class="text-sm text-blue-600 hover:underline" @click="() => switchGroupSelectNew(group)">
            {{ group.useNewGroup ? '🔁 Switch to existing group' : '➕ Create new group' }}
            </button>
        </div>

        <!-- Existing Group Dropdown -->
        <div v-if="!group.useNewGroup" class="mb-6">
            <vue-select :v-model="group.id" :options="filteredSearchGroups(index)" @search="(query) => emit('onSearchGroup', query, index)" :get-option-label="getGroupLabel" :disabled="!isGroupEditable(index)" placeholder="Search Group..." class="child-group-select" @update:modelValue="(val) => updateGroupId(group, val)">
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
              :modelValue="group"
              @update:modelValue="(newVal) => updateGroupData(group, newVal)"
              :index="index" 
              :isGroupEditable="isGroupEditable" 
              :filteredClassifications="filteredClassifications"
              :filteredLevels="filteredLevels"/>
        </div>

        <!-- Remove button -->
        <div class="text-right mt-4" v-if="index > 0 && isGroupEditable(index)">
            <button type="button" class="px-4 py-2 bg-red-500 text-white rounded-md" @click="() => emit('removeGroup', index)">
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
  emits: ['removeGroup', 'onSearchGroup', 'removeGroupFromPreselected', 'addGroupToPreselected'],
  setup(props, { emit }) {
    const switchGroupSelectNew = (group) => {
      group.useNewGroup = !group.useNewGroup;
      if (group.useNewGroup) {
        emit('removeGroupFromPreselected', group);
        group.id = null;
      } else {
        group.name = '';
        group.classification_id = null;
        group.level_id = 5;
        group.description = '';
        group.is_clade = false;
      }
    }

    const getGroupLabel = (group) => {
      const classification = props.getClassificationById(group.classification_id)
      const level = props.getLevelById(group.level_id);

      return `${group.name} - ${classification && level ? classification.name + ' - ' + level.name : '(Unranked Clade)'}`;
    };

    const updateGroupId = (currentGroup, newGroup) => {
      emit('removeGroupFromPreselected', currentGroup);
      emit('addGroupToPreselected', newGroup);
      currentGroup.id = newGroup?.id || null;
    };

    const updateGroupData = (group, newData) => {
      if (group.hasOwnProperty(newData.key)) {
        group[newData.key] = newData.value;
      }
    };

    return {
      emit,
      switchGroupSelectNew,
      updateGroupId,
      getGroupLabel,
      updateGroupData,
    };
  },
};
</script>

