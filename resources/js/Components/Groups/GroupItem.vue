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
            <!-- Group Name -->
            <label class="block font-semibold mb-1">Group Name</label>
            <input v-model="group.name" required :disabled="!isGroupEditable(index)" placeholder="Enter group name" class="w-full p-2 border border-gray-300 rounded-md mb-4"/>

            <!-- Clade Toggle Switch -->
            <div class="flex items-center gap-2 mb-4 flex-row-reverse">
            <label class="inline-flex relative items-center cursor-pointer">
                <input type="checkbox" v-model="group.is_clade" @change="(e) => cladeGroupToggle(group, e.target.checked)" class="sr-only peer" :disabled="!isGroupEditable(index)">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer peer-checked:bg-green-500 transition-all duration-300"></div>
                <div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-all duration-300 transform peer-checked:peer-checked:translate-x-[130%]"></div>
            </label>
            <label for="clade-toggle" class="font-semibold">Unranked Clade</label>
            </div>

            <div v-if="!group.is_clade">
            <!-- Classification -->
            <label class="block font-semibold mb-1">Classification</label>
            <select v-model="group.classification_id" :disabled="!isGroupEditable(index)" class="w-full p-2 border border-gray-300 rounded-md mb-4">
                <option disabled :value="null">Select classification</option>
                <option v-for="c in filteredClassifications(index)" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>

            <!-- Level -->
            <label class="block font-semibold mb-1">Level</label>
            <select v-model="group.level_id" :disabled="!isGroupEditable(index) || !group.classification_id" class="w-full p-2 border border-gray-300 rounded-md mb-4">
                <option disabled :value="null">Select level</option>
                <option v-for="l in filteredLevels(index, group.classification_id)" :key="l.id" :value="l.id">{{ l.name }}</option>
            </select>
            </div>

            <!-- Description -->
            <label class="block font-semibold mb-1">Description</label>
            <input v-model="group.description" :disabled="!isGroupEditable(index)" placeholder="Describe the group (optional)" class="w-full p-2 border border-gray-300 rounded-md"/>
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

    const cladeGroupToggle = (group, isOn) => {
      if (isOn) {
        group.classification_id = null;
        group.level_id = null;
      } else {
        group.level_id = 5;
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


    return {
      emit,
      switchGroupSelectNew,
      updateGroupId,
      getGroupLabel,
      cladeGroupToggle,
    };
  },
};
</script>

