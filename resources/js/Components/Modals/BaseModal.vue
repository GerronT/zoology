<template>
  <div v-if="visible" class="modal-overlay" @click.self="emitClose">
    <div class="modal-content">
      <slot />
    </div>
  </div>
</template>

<script>
import { toRefs } from 'vue';

export default {
  props: {
    visible: Boolean,
  },
  emits: ['close'],
  setup(props, { emit }) {
    const { visible } = toRefs(props);

    const emitClose = () => emit('close');

    return {
      visible,
      emitClose,
    };
  },
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
}
.modal-content {
  background: white;
  border-radius: 8px;
  max-width: 600px;
  width: 100%;
}
</style>
