<template>
  <BaseModal :visible="visible" @close="emitClose">
    <div class="mx-auto p-12 bg-gray-100 rounded-lg shadow-md">
        <h2 class="text-xl font-bold mb-6 text-center">{{title}}</h2>
        <form @submit.prevent="callParentFunction" class="animal-form">
            <!-- Animal Name and Alternate Name -->
            <p class="text-center my-8">{{description}}</p>
            <div class="flex justify-between gap-2 mt-6">
              <button type="button" class="px-4 py-2 bg-red-500 hover:brightness-75 text-white rounded-md disabled:bg-red-300 disabled:cursor-not-allowed" @click="emitClose" :disabled="inProgress">
                Cancel
              </button>
              <button type="submit" class="px-4 py-2 bg-green-500 hover:brightness-75 text-white rounded-md disabled:bg-green-300 disabled:cursor-not-allowed" :disabled="inProgress">
                {{button}}
              </button>
            </div>
        </form>
    </div>
  </BaseModal>
</template>

<script>
import { watch, ref } from 'vue';
import BaseModal from "./BaseModal.vue";
import GroupBaseForm from "../Groups/GroupBaseForm.vue";

export default {
  props: {
    visible: Boolean,
    title: String,
    description: String,
    button: String,
    functionCall: Function
  },
  components: {
    GroupBaseForm,
    BaseModal
  },
  emits: ['close'],
  setup(props, {emit}) {
    const title = ref('');
    const description = ref('');
    const button = ref('');

    const inProgress = ref(false);

    watch(() => props.visible, (newVal) => {
        if (newVal) {
          initializeModal();
        }
      }
    );

    const initializeModal = () => {
        title.value = props?.title;
        description.value = props?.description;
        button.value = props?.button;
    };

    const callParentFunction = async () => {
        inProgress.value = true;
        await props.functionCall();
        inProgress.value = false;
        emitClose();
    }

    const emitClose = () => emit('close');
    
    return {
        title,
        description,
        button,
        inProgress,
        callParentFunction,
        emitClose,
    };
  },
};
</script>