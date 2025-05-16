<template>
    <div>
        <!-- Animal Name and Alternate Name -->
        <div class="flex gap-4 mb-6">
            <div class="w-full">
                <label for="name" class="block font-semibold mb-1">Animal Name</label>
                <input id="name" v-model="animal.name" required placeholder="Enter animal name" class="w-full p-2 border border-gray-300 rounded-md"/>
            </div>
            <div class="w-full">
                <label for="alt_name" class="block font-semibold mb-1">Alternate Name</label>
                <input id="alt_name" v-model="animal.alt_name" placeholder="Enter alternate name (optional)" class="w-full p-2 border border-gray-300 rounded-md"/>
            </div>
        </div>

        <!-- Animal Description Field -->
        <div class="mb-6">
            <label for="description" class="block font-semibold mb-1">Animal Description</label>
            <textarea id="description" v-model="animal.description" placeholder="Describe the animal (optional)" rows="4" class="w-full p-2 border border-gray-300 rounded-md resize-y"></textarea>
        </div>
  </div>
</template>

<script>
import { toRefs, watch } from 'vue';

export default {
  props: {
    animal: {
      type: Object,
      required: true,
    },
  },
  setup(props, { emit }) {
    const { animal } = toRefs(props); // preserve reactivity to parent

    const emitFieldUpdate = (key, value) => {
      emit('update:animal', { key, value });
    };

    watch(() => animal.value.name, (newVal) => emitFieldUpdate('name', newVal));
    watch(() => animal.value.alt_name, (newVal) => emitFieldUpdate('alt_name', newVal));
    watch(() => animal.value.description, (newVal) => emitFieldUpdate('description', newVal));

    return {
      animal,
    };
  },
};
</script>
