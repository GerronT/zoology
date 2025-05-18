<template>
  <BaseModal :visible="visible" @close="emitClose">
    <div class="mx-auto p-6 bg-gray-100 rounded-lg shadow-md">
      <slot name="header"></slot>
      <slot name="body"></slot>
      <div class="flex justify-between gap-2 mt-6 items-end text-sm">
        <button type="button" class="px-4 py-2 bg-gray-300 enabled:hover:brightness-75 text-gray-600 rounded-md disabled:bg-gray-200 disabled:cursor-not-allowed" @click="emitClose" :disabled="inProgress">
          Close
        </button>
        <slot name="extraButtons"></slot>
      </div>
    </div>
  </BaseModal>
</template>

<script>
import BaseModal from "./BaseModal.vue";
import GroupBaseForm from "../Groups/GroupBaseForm.vue";

export default {
  props: {
    visible: Boolean,
    inProgress: Boolean
  },
  components: {
    GroupBaseForm,
    BaseModal
  },
  emits: ['close'],
  setup(props, {emit}) {
    const emitClose = () => emit('close');

    return {
        emitClose,
    };
  },
};
</script>