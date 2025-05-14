<template>
  <ul class="relative inline-flex">
    <span class="rounded-full" :class="node.is_root ? 'bg-red-700 w-5 h-5 mt-2' : 'w-3 h-3 bg-yellow-500 mt-3 -ml-[5px]'"></span>
    <div class="border-t-2 border-orange-300 mt-4 w-8"></div>
    <li
      class="relative before:absolute before:top-0 before:left-6 before:h-full before:border-l-2 before:border-orange-300"
      :id="`node-${node.id}`"
    >
      <div class="relative inline-flex items-center">
          <div
            class="relative inline-flex items-center gap-1 border-l-2 border-t-2 border-b-2 rounded-tl rounded-bl px-2 py-1 cursor-pointer"
            draggable="true"
            @dragstart="onDragStart"
            @dragover.prevent="handleDragOver"
            @dragleave="handleDragLeave"
            @drop="onDrop"
            @click="toggle"
            @mouseup="(e) => mouseUpMainCanvas()"
            @mouseleave="(e) => mouseUpMainCanvas()"
            :class="!draggedOver ? 'bg-orange-100 border-orange-300 hover:bg-orange-200' : 'bg-green-200 border-green-300 hover:bg-green-200'"
          >
            <span v-if="hasChildren" class="w-4 text-xs text-orange-500">{{ isOpen ? '▼' : '▶' }}</span>
            <strong class="text-sm text-gray-800">{{ node.name }}</strong>
            <span v-if="node.classification" class="text-xs text-gray-500">({{ node.classification }})</span>
            <span v-if="node.level" class="text-xs text-gray-400">- Level: {{ node.level }}</span>
            <span v-if="!node.classification && !node.level" class="text-xs text-gray-400 italic">- (Unranked Clade)</span>
          </div>

          <div class="flex items-center gap-2 px-2 bg-orange-200 border-orange-400 border-t-2 border-b-2 border-l-2" v-show="showMenu">
            <button class="h-6 w-6 flex items-center justify-center px-2 py-1 text-xs font-medium text-green-800 bg-green-100 border-2 border-green-300 rounded hover:bg-green-200 transition" title="Add">
              ➕
            </button>
            <button class="h-6 w-6 flex items-center justify-center px-2 py-1 text-xs font-medium text-blue-800 bg-blue-100 border-2 border-blue-300 rounded hover:bg-blue-200 transition" title="Edit">
              ✏️
            </button>
            <button class="h-6 w-6 flex items-center justify-center px-2 py-1 text-xs font-medium text-red-800 bg-red-100 border-2 border-red-300 rounded hover:bg-red-200 transition" title="Delete">
              🗑️
            </button>
          </div>
          <button @click.prevent="showMenu = !showMenu" title="Open Menu" class="bg-orange-200 hover:bg-orange-300 border-orange-400 text-orange-500 rounded-tr-md rounded-br-md border-b-2 border-t-2 border-r-2 border-l-2 px-1 font-bold">{{ showMenu ? '✖' : '☰' }}</button>
      </div>

      <!-- Animals list -->
      <ul v-show="node.animals?.length" class="ml-6 flex flex-row">
        <div class="border-t-2 border-purple-300 mt-4 w-8"></div>
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
            @add-line="addLineToParent"
            @reassign-parent="$emit('reassign-parent', $event)"
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

const emit = defineEmits(['add-line', 'reassign-parent']);

const draggedOver = ref(false);

function handleDragOver(event) {
  event.preventDefault();
  draggedOver.value = true;
}

function handleDragLeave() {
  draggedOver.value = false;
}

const showMenu = ref(false);

const isOpen = ref(false);
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
