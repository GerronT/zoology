<template>
  <transition name="modal-fade">
    <div v-if="visible" class="modal-overlay fixed inset-0 bg-opacity-60 flex justify-center items-center z-50" @click.self="emitClose">
      <div class="modal-content bg-white rounded-lg w-full max-w-lg">
        <slot />
      </div>
    </div>
  </transition>
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
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
  transform: scale(0.9);
}

.modal-fade-enter-to,
.modal-fade-leave-from {
  opacity: 1;
  transform: scale(1);
}

.modal-overlay {
  z-index: 50;
}
</style>
