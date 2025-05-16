<template>
    <div>
        <!-- Animal Name and Alternate Name -->
        <div class="flex gap-4 mb-6">
            <div class="w-full">
                <label for="name" class="block font-semibold mb-1">Animal Name</label>
                <input id="name" v-model="form.name" required placeholder="Enter animal name" class="w-full p-2 border border-gray-300 rounded-md"/>
            </div>
            <div class="w-full">
                <label for="alt_name" class="block font-semibold mb-1">Alternate Name</label>
                <input id="alt_name" v-model="form.alt_name" placeholder="Enter alternate name (optional)" class="w-full p-2 border border-gray-300 rounded-md"/>
            </div>
        </div>

        <!-- Animal Description Field -->
        <div class="mb-6">
            <label for="description" class="block font-semibold mb-1">Animal Description</label>
            <textarea id="description" v-model="form.description" placeholder="Describe the animal (optional)" rows="4" class="w-full p-2 border border-gray-300 rounded-md resize-y"></textarea>
        </div>
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
  },
  setup(props, { emit }) {
    const { modelValue } = toRefs(props); // preserve reactivity to parent

    const emitFieldUpdate = (key, value) => {
      emit('update:modelValue', { key, value });
    };

    // Watch each field
    watch(() => modelValue.value.name, (newVal) => emitFieldUpdate('name', newVal));
    watch(() => modelValue.value.alt_name, (newVal) => emitFieldUpdate('alt_name', newVal));
    watch(() => modelValue.value.description, (newVal) => emitFieldUpdate('description', newVal));

    return {
      form: modelValue,
    };
  },
};
</script>
