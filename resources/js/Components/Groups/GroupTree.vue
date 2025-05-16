<template>
  <ul class="relative inline-flex">
    <span class="rounded-full" :class="node.is_root ? 'bg-red-700 w-5 h-5 mt-4' : 'w-3 h-3 bg-yellow-500 mt-5 -ml-[5px]'"></span>
    <div class="border-t-2 border-orange-300 mt-6 w-8 border-dashed"></div>
    <li
      class="relative before:absolute before:left-6 before:border-l-2 before:border-orange-300 mt-2 before:top-0 before:h-full before:border-dotted"
      :id="`node-${node.id}`"
    >
      <div class="relative inline-flex items-center">
          <div
            class="relative inline-flex items-center gap-1 border-2 rounded-md px-2 py-1 cursor-pointer hover:brightness-75"
            draggable="true"
            @dragstart="onDragStart"
            @dragover.prevent="handleDragOver"
            @dragleave="handleDragLeave"
            @drop="onDrop"
            @click="toggle"
            @mouseup="(e) => mouseUpMainCanvas()"
            @mouseleave="(e) => mouseUpMainCanvas()"
            :class="`bg-gradient-to-r from-${node.primary_class} to-${node.secondary_class} border-orange-300` + (draggedOver ? ` brightness-200` : ``)"
          >
            <span v-if="hasChildren" class="w-4 text-xs text-orange-500">{{ isOpen ? '▼' : '▶' }}</span>
            <strong class="text-sm text-gray-800">{{ node.name }}</strong>
            <span v-if="node.classification" class="text-xs text-gray-500">({{ node.classification }})</span>
            <span v-if="node.level" class="text-xs text-gray-400">- Level: {{ node.level }}</span>
            <span v-if="!node.classification && !node.level" class="text-xs text-gray-400 italic">- (Unranked Clade)</span>
          </div>

          <div class="absolute right-0 mb-8 -mr-4">
          <div class="flex items-center gap-1">
            <button @click="openModal({type: 'add', group: props.node})" class="h-4 w-4 flex items-center justify-center text-[8px] font-medium text-green-800 bg-green-100 border-2 border-green-300 rounded hover:bg-green-200 transition" title="Add">
              ➕
            </button>
            <button @click="openModal({type: 'edit', group: props.node})" class="h-4 w-4 flex items-center justify-center text-[8px] font-medium text-blue-800 bg-blue-100 border-2 border-blue-300 rounded hover:bg-blue-200 transition" title="Edit">
              ✏️
            </button>
            <button class="h-4 w-4 flex items-center justify-center text-[8px] font-medium text-red-800 bg-red-100 border-2 border-red-300 rounded hover:bg-red-200 transition" title="Delete">
              🗑️
            </button>
          </div>
          </div>
      </div>

      <!-- Animals list -->
      <ul v-show="node.animals?.length" class="ml-6 flex flex-row">
        <span class="rounded-full w-3 h-3 bg-purple-400 mt-4 -ml-[5px] relative"></span>
        <div class="border-t-2 border-purple-300 mt-5 w-8"></div>
        <li
          v-for="animal in node.animals"
          :key="animal.id"
          class="text-sm text-gray-600 italic bg-purple-100 hover:bg-purple-200 border-2 border-purple-300 p-1 rounded-md mt-2 pr-4"
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
        <ul v-show="isOpen && hasChildren" class="ml-6 mt-2 space-y-2 flex flex-col">
          <GroupTree
            v-for="child in node.children"
            :key="child.id"
            :node="child"
            :open_nodes="open_nodes"
            @add-line="addLineToParent"
            @reassign-parent="$emit('reassign-parent', $event)"
            @open-modal="(data) => openModal(data)"
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
  },
  open_nodes: Boolean
});

const emit = defineEmits(['add-line', 'reassign-parent', 'open-modal']);

const draggedOver = ref(false);

function openModal(data) {
  emit('open-modal', data);
}

function handleDragOver(event) {
  event.preventDefault();
  draggedOver.value = true;
}

function handleDragLeave() {
  draggedOver.value = false;
}

const isOpen = ref(props.open_nodes);
const hasChildren = computed(() => props.node.children && props.node.children.length > 0);

// Toggle child visibility
function toggle() {
  if (hasChildren.value) isOpen.value = !isOpen.value;
}

// Add connection line
const addLineToParent = () => {
  const parentPosition = document.getElementById(`node-${props.node.id}`).getBoundingClientRect();
  const parentX = parentPosition.left + parentPosition.width / 2;
  const parentY = parentPosition.top + parentPosition.height;

  props.node.children.forEach((child) => {
    const childPosition = document.getElementById(`node-${child.id}`).getBoundingClientRect();
    const childX = childPosition.left + childPosition.width / 2;
    const childY = childPosition.top;

    emit('add-line', {
      id: `${props.node.id}-${child.id}`,
      x1: parentX,
      y1: parentY,
      x2: childX,
      y2: childY
    });
  });
};

// Drag-and-drop handlers
function onDragStart(event) {
  event.dataTransfer.setData('application/json', JSON.stringify(props.node));
}

function onDrop(event) {
  const draggedData = JSON.parse(event.dataTransfer.getData('application/json'));

  mouseUpMainCanvas();
  handleDragLeave();
  // Prevent dropping onto self
  if (draggedData.id === props.node.id) return;

  // Emit parent reassignment event
  emit('reassign-parent', {
    childId: draggedData.id,
    newParentId: props.node.id
  });
}

function mouseUpMainCanvas(elementId = "mainCanvas") {
  const element = document.getElementById(elementId);
  if (element) {
    const mouseUpEvent = new MouseEvent('mouseup', {
      bubbles: true,
      cancelable: true,
      view: window,
    });
    element.dispatchEvent(mouseUpEvent);
  }
}


</script>

<style scoped>
li {
  cursor: move;
}
</style>
