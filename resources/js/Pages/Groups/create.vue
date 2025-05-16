<template>
  <div class="max-w-xl mx-auto p-6 bg-gray-100 rounded-lg shadow-md my-6">
    <h2 class="text-center text-xl font-bold mb-6">Add Group(s)</h2>
    <form @submit.prevent="submitGroups" class="group-form">
      <!-- Groupings section -->
      <group-form :classifications="classifications" :levels="levels" :groupings="form.groupings" @update:groups="updateGroupData" @addGroup="addGroup" @removeGroup="removeGroup">
        <template #cta>
          <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md disabled:bg-gray-400 disabled:cursor-not-allowed" :disabled="!isSaveGroupEnabled">
            💾 Save Group(s)
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
import GroupForm from "../../Components/Groups/GroupForm.vue";

export default {
  props: {
    classifications: Array,
    levels: Array,
  },
  components: {
    GroupForm
  },
  setup(props) {
    const form = reactive({
      groupings: [{id: null, name: '', classification_id: null, level_id: 5, description: '', is_clade: false , useNewGroup: false}]
    });

    const addGroup = (useNewGroup) => {
      form.groupings.push({id: null, name: '', classification_id: null, level_id: 5, description: '', is_clade: false, useNewGroup: useNewGroup ?? false});
    }

    const removeGroup = (index) => {
      form.groupings.splice(index, 1);
    };

    const updateGroupData = (index, newData) => {
      form.groupings[index][newData.key] = newData.value;
    };

    // Computed logic for enabling the 'Save Group' button
    const isSaveGroupEnabled = computed(() => {
      return true;
    });

    // Submit the group(s) form
    const submitGroups = async () => {
      try {
        await axios.post('/groups', {
          ...form
        });
        alert('Group(s) added!');
        location.reload();
      } catch (e) {
        alert('Error creating group');
      }
    };

    return {
      form,

      addGroup,
      removeGroup,
      updateGroupData,

      isSaveGroupEnabled,
      submitGroups,
    };
  },
};
</script>

