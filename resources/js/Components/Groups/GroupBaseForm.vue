<template>
    <div>
        <!-- Group Name -->
        <label class="block font-semibold mb-1">Group Name</label>
        <input v-model="form.name" required :disabled="!isGroupEditable(index)" placeholder="Enter group name" class="w-full p-2 border border-gray-300 rounded-md mb-4"/>

        <!-- Clade Toggle Switch -->
        <div class="flex items-center gap-2 mb-4 flex-row-reverse">
        <label class="inline-flex relative items-center cursor-pointer">
            <input type="checkbox" v-model="form.is_clade" @change="(e) => cladeGroupToggle(form, e.target.checked)" class="sr-only peer" :disabled="!isGroupEditable(index)">
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer peer-checked:bg-green-500 transition-all duration-300"></div>
            <div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-all duration-300 transform peer-checked:peer-checked:translate-x-[130%]"></div>
        </label>
        <label for="clade-toggle" class="font-semibold">Unranked Clade</label>
        </div>

        <div v-if="!form.is_clade">
        <!-- Classification -->
        <label class="block font-semibold mb-1">Classification</label>
        <select v-model="form.classification_id" :disabled="!isGroupEditable(index)" class="w-full p-2 border border-gray-300 rounded-md mb-4">
            <option disabled :value="null">Select classification</option>
            <option v-for="c in filteredClassifications(index)" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>

        <!-- Level -->
        <label class="block font-semibold mb-1">Level</label>
        <select v-model="form.level_id" :disabled="!isGroupEditable(index) || !form.classification_id" class="w-full p-2 border border-gray-300 rounded-md mb-4">
            <option disabled :value="null">Select level</option>
            <option v-for="l in filteredLevels(index, form.classification_id)" :key="l.id" :value="l.id">{{ l.name }}</option>
        </select>
        </div>

        <!-- Description -->
        <label class="block font-semibold mb-1">Description</label>
        <input v-model="form.description" :disabled="!isGroupEditable(index)" placeholder="Describe the group (optional)" class="w-full p-2 border border-gray-300 rounded-md"/>
    </div>
</template>

<script>
import { toRefs, watch } from 'vue';

export default {
  props: {
    modelValue: {
      type: Object,
      required: true,
    },
    index: Number,
    isGroupEditable: Function,
    filteredClassifications: Function,
    filteredLevels: Function
  },
  setup(props, { emit }) {
    const { modelValue } = toRefs(props); // preserves reactivity

    // Emit updates when any field changes
    const emitFieldUpdate = (key, value) => {
      emit('update:modelValue', { key, value });
    };

    // Watch each field individually
    watch(() => modelValue.value.name, (newVal) => emitFieldUpdate('name', newVal));
    watch(() => modelValue.value.classification_id, (newVal) => emitFieldUpdate('classification_id', newVal));
    watch(() => modelValue.value.level_id, (newVal) => emitFieldUpdate('level_id', newVal));
    watch(() => modelValue.value.description, (newVal) => emitFieldUpdate('description', newVal));
    watch(() => modelValue.value.is_clade, (newVal) => emitFieldUpdate('is_clade', newVal));

    const cladeGroupToggle = (group, isOn) => {
      if (isOn) {
        emitFieldUpdate('classification_id', null);
        emitFieldUpdate('level_id', null);
      } else {
        emitFieldUpdate('level_id', 5);
      }
    };

    return {
      form: modelValue, // still reactive
      cladeGroupToggle,
    };
  },
};
</script>


