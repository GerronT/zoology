<template>
  <ul class="relative pl-6">
    <li
      class="relative before:absolute before:top-0 before:left-0 before:h-full before:border-l-2 before:border-blue-300"
      :id="`node-${node.id}`"
    >
      <div
        class="relative inline-flex items-center gap-1 border-2 border-blue-300 rounded px-2 py-1 bg-white hover:bg-gray-100 cursor-pointer"
        @click="toggle"
      >
        <span v-if="hasChildren" class="w-4 text-xs text-gray-500">{{ isOpen ? '▼' : '▶' }}</span>
        <strong class="text-sm text-gray-800">{{ node.name }}</strong>
        <span v-if="node.classification" class="text-xs text-gray-500">({{ node.classification }})</span>
        <span v-if="node.level" class="text-xs text-gray-400">- Level: {{ node.level }}</span>
        <span v-if="!node.classification && !node.level" class="text-xs text-gray-400 italic">- (Unranked Clade)</span>
      </div>

      <!-- Animals (only visible when open) -->
      <ul v-show="node.animals?.length" class="ml-6 mt-1">
        <li
          v-for="animal in node.animals"
          :key="animal.id"
          class="text-sm text-gray-600 italic"
        >
          🐾 {{ animal.name }}
          <span v-if="animal.alt_name">({{ animal.alt_name }})</span>
        </li>
      </ul>

      <!-- Children (recursive) -->
      <transition
        enter-active-class="transition duration-300 ease-out"
        leave-active-class="transition duration-200 ease-in"
        enter-from-class="opacity-0 max-h-0"
        enter-to-class="opacity-100 max-h-[1000px]"
        leave-from-class="opacity-100 max-h-[1000px]"
        leave-to-class="opacity-0 max-h-0"
      >
        <ul v-show="isOpen && hasChildren" class="ml-6 mt-2 space-y-2">
          <GroupTree
            v-for="child in node.children"
            :key="child.id"
            :node="child"
            @add-line="addLineToParent"
          />
        </ul>
      </transition>
    </li>
  </ul>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  node: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['add-line']);

const isOpen = ref(false);  // Default to closed for all nodes
const hasChildren = computed(() => props.node.children && props.node.children.length > 0);

function toggle() {
  if (hasChildren.value) isOpen.value = !isOpen.value;
}

// Handle adding line to the canvas
const addLineToParent = () => {
  const parentPosition = document.getElementById(`node-${props.node.id}`).getBoundingClientRect();
  const parentX = parentPosition.left + parentPosition.width / 2;
  const parentY = parentPosition.top + parentPosition.height;

  props.node.children.forEach((child) => {
    const childPosition = document.getElementById(`node-${child.id}`).getBoundingClientRect();
    const childX = childPosition.left + childPosition.width / 2;
    const childY = childPosition.top;

    // Emit line coordinates to the parent
    emit('add-line', {
      id: `${props.node.id}-${child.id}`,
      x1: parentX,
      y1: parentY,
      x2: childX,
      y2: childY
    });
  });
};
</script>

<style scoped>
/* Additional styling for draggable nodes */
li {
  cursor: move;
}
</style>
