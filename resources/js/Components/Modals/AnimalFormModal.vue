<template>
  <BaseModal :visible="visible" @close="emit('close')">
    <div class="max-w-xl mx-auto p-6 bg-gray-100 rounded-lg shadow-md my-6">
        <h2 class="text-center text-xl font-bold mb-6">Add Animal</h2>
        <form @submit.prevent="submitAnimal" class="animal-form">
            <!-- Animal Name and Alternate Name -->
            <animal-form :animal="animalForm" @update:animal="updateAnimalForm"/>
        </form>
    </div>
  </BaseModal>
</template>

<script>
import { reactive, computed } from 'vue';
import axios from 'axios';
import AnimalForm from "./Animals/AnimalForm.vue";

export default {
  props: {
    parentGroup: Object,
    visible: Boolean,
  },
  components: {
    AnimalForm,
    BaseModal
  },
  emits: ['close'],
  setup(props, {emit}) {
    const animalForm = reactive({
      name: '',
      alt_name: '',
      description: '',
    });

    const updateAnimalForm = (newData) => {
      animalForm[newData.key] = newData.value;
    };

    // Submit form
    const submitAnimal = async () => {
      try {
        await axios.post('/animals', {
          ...animalForm
        });
        alert('Animal added!');
        location.reload();
      } catch (e) {
        alert('Error creating animal');
      }
    };

    const isSaveAnimalEnabled = computed(() => {
        return true;
    });
    
    return {
        emit,
        animalForm,
        updateAnimalForm,
        isSaveAnimalEnabled,
        submitAnimal,
    };
  },
};
</script>