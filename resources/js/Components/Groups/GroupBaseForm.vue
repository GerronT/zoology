<template>
    <div>
        <!-- Group Name -->
        <label class="block font-semibold mb-1">Group Name</label>
        <input v-model="group.name" required :disabled="!isGroupEditable()" placeholder="Enter group name" class="w-full p-2 border border-gray-300 rounded-md mb-4"/>

        <!-- Clade Toggle Switch -->
        <div class="flex items-center gap-2 mb-4 flex-row-reverse">
        <label class="inline-flex relative items-center cursor-pointer">
            <input type="checkbox" v-model="group.is_clade" @change="(e) => cladeGroupToggle(e.target.checked)" class="sr-only peer" :disabled="!isGroupEditable() || filteredClassifications().length == 0">
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer peer-checked:bg-green-500 transition-all duration-300"></div>
            <div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-all duration-300 transform peer-checked:peer-checked:translate-x-[130%]"></div>
        </label>
        <label for="clade-toggle" class="font-semibold">Unranked Clade</label>
        </div>

        <div v-if="!group.is_clade">
        <!-- Classification -->
        <label class="block font-semibold mb-1">Classification</label>
        <select v-model="group.classification_id" required :disabled="!isGroupEditable()" class="w-full p-2 border border-gray-300 rounded-md mb-4">
            <option disabled :value="''">Select classification</option>
            <option v-for="c in filteredClassifications()" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>

        <!-- Level -->
        <label class="block font-semibold mb-1">Level</label>
        <select v-model="group.level_id" required :disabled="!isGroupEditable() || !group.classification_id" class="w-full p-2 border border-gray-300 rounded-md mb-4">
            <option disabled :value="''">Select level</option>
            <option v-for="l in filteredLevels()" :key="l.id" :value="l.id">{{ l.name }}</option>
        </select>
        </div>

        <!-- Description -->
        <label class="block font-semibold mb-1">Description</label>
        <input v-model="group.description" :disabled="!isGroupEditable()" placeholder="Describe the group (optional)" class="w-full p-2 border border-gray-300 rounded-md"/>
    </div>
</template>

<script>
import { toRefs, watch } from 'vue';

export default {
  props: {
    group: Object,
    isGroupEditable: {
      type: Function,
      default: () => () => true
    },
    filteredClassifications: {
      type: Function,
      default: () => () => []
    },
    filteredLevels: {
      type: Function,
      default: () => () => []
    }
  },
  setup(props, { emit }) {
    const { group } = toRefs(props); // preserves reactivity

    // Emit updates when any field changes
    const updateGroupField = (key, value) => {
      emit('update:group', key, value);
    };

    // Watch each field individually
    watch(() => group.value.name, (newVal) => updateGroupField('name', newVal));
    watch(() => group.value.classification_id, (newVal) => updateGroupField('classification_id', newVal));
    watch(() => group.value.level_id, (newVal) => updateGroupField('level_id', newVal));
    watch(() => group.value.description, (newVal) => updateGroupField('description', newVal));
    watch(() => group.value.is_clade, (newVal) => updateGroupField('is_clade', newVal));

    const cladeGroupToggle = (isOn) => {
      if (isOn) {
        updateGroupField('classification_id', '');
        updateGroupField('level_id', '');
      } else {
        updateGroupField('classification_id', '');
        updateGroupField('level_id', 5);
      }
    };

    return {
      group,
      cladeGroupToggle,
    };
  },
};
</script>


