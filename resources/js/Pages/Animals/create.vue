<template>
  <div class="max-w-xl mx-auto p-6 bg-gray-100 rounded-lg shadow-md my-6">
    <h2 class="text-center text-xl font-bold mb-6">Add Animal</h2>
    <form @submit.prevent="submitAnimal" class="animal-form">
      <!-- Animal Name and Alternate Name -->
      <animal-form v-model="form"/>

      <!-- Groupings section -->
      <group-form :classifications="classifications" :levels="levels" :groupings="form.groupings" @addGroup="addGroup" @removeGroup="removeGroup">
        <template #cta>
          <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md disabled:bg-gray-400 disabled:cursor-not-allowed" :disabled="!isSaveAnimalEnabled">
            💾 Save Animal
          </button>
        </template>
      </group-form>
    </form>
  </div>
</template>

<script>
import { reactive, computed } from 'vue';
import axios from 'axios';
import 'vue3-select/dist/vue3-select.css';
import AnimalForm from "../../Components/Animals/AnimalForm.vue";
import GroupForm from "../../Components/Groups/GroupForm.vue";

export default {
  props: {
    classifications: Array,
    levels: Array,
  },
  components: {
    AnimalForm,
    GroupForm
  },
  setup(props) {
    const form = reactive({
      name: '',
      alt_name: '',
      description: '',
      groupings: [{id: null, name: '', classification_id: null, level_id: 5, description: '', is_clade: false , useNewGroup: false}]
    });

    const updateFormData = (newFormData) => {
      form.value = { ...formData.value, ...newFormData };
    };

    const addGroup = (useNewGroup) => {
      form.groupings.push({id: null, name: '', classification_id: null, level_id: 5, description: '', is_clade: false, useNewGroup: useNewGroup ?? false});
    }

    const removeGroup = (index) => {
      form.groupings.splice(index, 1);
    };

    // Computed logic for enabling the 'Save Animal' button
    const isSaveAnimalEnabled = computed(() => {
      // const actGroup = activeGroup.value;

      // just validate on the backend

      // if (actGroup) {
      //   const requiredFieldsAreFilled = actGroup.useNewGroup ? actGroup.name && (actGroup.is_clade || actGroup.classification_id && actGroup.level_id) : actGroup.id;
      //   if (!requiredFieldsAreFilled) return false;

      //   const lastClassSelected = isLastRanked(actGroup.classification_id, classificationRanks.value);
      //   if (!lastClassSelected) return false;

      //   const preselectedActiveGroup = actGroup.id;
      //   if (preselectedActiveGroup) return false;

      //   return true;
      // }

      return true;
    });

    // Submit the animal form
    const submitAnimal = async () => {
      try {
        await axios.post('/animals', {
          ...form
        });
        alert('Animal added!');
        location.reload();
      } catch (e) {
        alert('Error creating animal');
      }
    };

    return {
      form,
      updateFormData,

      addGroup,
      removeGroup,

      isSaveAnimalEnabled,
      submitAnimal,
    };
  },
};
</script>

