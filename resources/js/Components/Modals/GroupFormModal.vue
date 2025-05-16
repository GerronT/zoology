<template>
  <BaseModal :visible="visible" @close="emitClose">
    <div class="mx-auto p-12 bg-gray-100 rounded-lg shadow-md">
        <h2 class="text-center text-xl font-bold mb-6">{{title}}</h2>
        <form @submit.prevent="submitGroup" class="animal-form">
            <!-- Animal Name and Alternate Name -->
            <group-base-form :group="groupForm" @update:group="updateGroupForm" :filteredClassifications="filteredClassifications" :filteredLevels="filteredLevels"/>
        </form>
    </div>
  </BaseModal>
</template>

<script>
import { reactive, computed, watch, ref } from 'vue';
import axios from 'axios';
import BaseModal from "./BaseModal.vue";
import GroupBaseForm from "../Groups/GroupBaseForm.vue";
import { useStore } from 'vuex';

export default {
  props: {
    data: Object,
    visible: Boolean,
    classifications: Array,
    levels: Array
  },
  components: {
    GroupBaseForm,
    BaseModal
  },
  emits: ['close'],
  setup(props, {emit}) {
    
  const store = useStore();

    const classificationRanks = computed(() => store.getters.getClassificationRanks);
    const levelRanks = computed(() => store.getters.getLevelRanks);

    const defaultGroupForm = {
      name: '',
      classification_id: null,
      level_id: 5,
      description: '',
      is_clade: false
    };
    
    const groupForm = reactive({...defaultGroupForm});
    
    const title = ref('');

    watch(() => props.visible, (newVal) => {
        if (newVal) {
          initializeModal();
        }
      }
    );

    const initializeModal = () => {
        if (props.data) {
          const type = props.data?.type;
          const group = props.data?.group;

          switch (type) {
            case 'edit':
              title.value = "Edit Group";
              groupForm.name = group.name;
              groupForm.classification_id = group.classification_id;
              groupForm.level_id = group.level_id;
              groupForm.description = group.description;
              groupForm.is_clade = !group.classification_id || !group.level_id;
              break;
            case 'add':
              title.value = "Add Child Group";
              Object.assign(groupForm, defaultGroupForm);
              break;
            default:
          }
        }
    };

    const filteredClassifications = () => {
      return props.classifications;
    }

    const filteredLevels = () => {
      return props.levels;
    }

    const emitClose = () => emit('close');

    const updateGroupForm = (newData) => {
      groupForm[newData.key] = newData.value;
    };

    const isSaveGroupEnabled = computed(() => {
        return true;
    });

    // Submit form
    const submitGroup = async () => {
      try {
        await axios.post('/groups', {
          ...groupForm
        });
        alert('Group added!');
        location.reload();
      } catch (e) {
        alert('Error creating group');
      }
    };
    
    return {
        title,
        emitClose,
        filteredClassifications,
        filteredLevels,
        groupForm,
        updateGroupForm,
        isSaveGroupEnabled,
        submitGroup,
    };
  },
};
</script>