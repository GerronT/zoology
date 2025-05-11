<template>
    <div>
        <!-- Animal Name and Alternate Name -->
        <div class="flex gap-4 mb-6">
            <div class="w-full">
                <label for="name" class="block font-semibold mb-1">Animal Name</label>
                <input id="name" v-model="localFormData.name" required placeholder="Enter animal name" class="w-full p-2 border border-gray-300 rounded-md"/>
            </div>
            <div class="w-full">
                <label for="alt_name" class="block font-semibold mb-1">Alternate Name</label>
                <input id="alt_name" v-model="localFormData.alt_name" placeholder="Enter alternate name (optional)" class="w-full p-2 border border-gray-300 rounded-md"/>
            </div>
        </div>

        <!-- Animal Description Field -->
        <div class="mb-6">
            <label for="description" class="block font-semibold mb-1">Animal Description</label>
            <textarea id="description" v-model="localFormData.description" placeholder="Describe the animal (optional)" rows="4" class="w-full p-2 border border-gray-300 rounded-md resize-y"></textarea>
        </div>
  </div>
</template>

<script>
import { reactive, watch } from 'vue';

export default {
  props: {
    modelValue: {
      type: Object,
      required: true,
    },
  },
  setup(props, { emit }) {
    // Make sure the form data is reactive
    const localFormData = reactive({ ...props.modelValue });

    // Watch for changes in localFormData and emit them
    watch(
      () => localFormData,
      (newValue) => {
        emit('update:modelValue', newValue);
      },
      { deep: true }
    );

    return {
      localFormData,
    };
  },
};
</script>