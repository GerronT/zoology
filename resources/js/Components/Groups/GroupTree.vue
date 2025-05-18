<template>
  <ul class="relative inline-flex">
    <span class="rounded-full" :class="self.is_root ? 'bg-red-700 w-5 h-5 mt-4' : 'w-3 h-3 bg-yellow-500 mt-5 -ml-[5px]'"></span>
    <div class="border-t-2 border-orange-300 mt-6 w-8 border-dashed"></div>
    <li class="relative before:absolute before:left-6 before:border-l-2 before:border-orange-300 mt-2 before:top-0 before:h-full before:border-dotted" :id="`node-${self.id}`">
      <div class="relative inline-flex items-center">
          <div class="relative inline-flex items-center gap-1 border-2 rounded-md px-2 py-1 cursor-pointer hover:brightness-75" draggable="true" @dragstart="onDragStart" @dragover.prevent="handleDragOver" @dragleave="handleDragLeave" @drop="onDrop" @click="toggle" @mouseup="(e) => mouseUpMainCanvas()" @mouseleave="(e) => mouseUpMainCanvas()" :class="`bg-gradient-to-r from-${self.primary_class} to-${self.secondary_class} border-orange-300` + (draggedOver ? ` brightness-200` : ``) + (isFetchingData ? ' cursor-wait' : ' cursor-pointer')">
            <span v-if="self.has_children" class="w-4 text-xs text-orange-500">{{ isOpen ? '▼' : '▶' }}</span>
            <strong class="text-sm text-gray-800">{{ self.name }}</strong>
            <span v-if="self.classification" class="text-xs text-gray-500">({{ self.classification }})</span>
            <span v-if="self.level" class="text-xs text-gray-400">- Level: {{ self.level }}</span>
            <span v-if="!self.classification && !self.level" class="text-xs text-gray-400 italic">- (Unranked Clade)</span>
          </div>

          <div class="absolute right-0 mb-8 -mr-4">
            <div class="flex items-center gap-1">
              <button @click="openAddGroupModal({type: 'add', group: self})" class="h-4 w-4 flex items-center justify-center text-[8px] font-medium text-green-800 bg-green-100 border-2 border-green-300 rounded hover:bg-green-200 transition" title="Add">➕</button>
              <button @click="openEditGroupModal({type: 'edit', group: self})" class="h-4 w-4 flex items-center justify-center text-[8px] font-medium text-blue-800 bg-blue-100 border-2 border-blue-300 rounded hover:bg-blue-200 transition" title="Edit">✏️</button>
              <button @click="openDeleteConfirmationModal(self)" class="h-4 w-4 flex items-center justify-center text-[8px] font-medium text-red-800 bg-red-100 border-2 border-red-300 rounded hover:bg-red-200 transition" title="Delete">🗑️</button>
            </div>
          </div>
      </div>

      <!-- Animals list -->
      <ul v-show="self.animals?.length" class="ml-6 flex flex-row">
        <span class="rounded-full w-3 h-3 bg-purple-400 mt-4 -ml-[5px] relative"></span>
        <div class="border-t-2 border-purple-300 mt-5 w-8"></div>
        <li v-for="animal in self.animals" :key="animal.id" class="text-sm text-gray-600 italic bg-purple-100 hover:bg-purple-200 border-2 border-purple-300 p-1 rounded-md mt-2 pr-4">
          🐾 {{ animal.name }} <span v-if="animal.alt_name">({{ animal.alt_name }})</span>
        </li>
      </ul>

      <!-- Children (recursive) -->
      <transition enter-active-class="transition duration-300 ease-out" leave-active-class="transition duration-200 ease-in" enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-[1000px]" leave-from-class="opacity-100 max-h-[1000px]" leave-to-class="opacity-0 max-h-0">
        <ul v-show="isOpen && self.has_children" class="ml-6 mt-2 space-y-2 flex flex-col">
          <GroupTree v-for="child in children" :key="child.id" :node="child" />
        </ul>
      </transition>
    </li>
  </ul>
</template>

<script setup>
import { ref, computed, watch, inject, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
  node: {
    type: Object,
    required: true
  },
});

const emitter = inject('emitter');
const openAddGroupModal = inject('openAddGroupModal');
const openEditGroupModal = inject('openEditGroupModal');
const openDeleteConfirmationModal = inject('openDeleteConfirmationModal');
const moveGroup = inject('moveGroup');

const self = ref({ ...props.node }); // shallow clone
watch(() => props.node, (newData) => {
  self.value = { ...newData };
});

onMounted(() => {
  emitter.on(`update:${self.value.id}`, async (data) => {
    await processUpdate(data);
  });
});

onBeforeUnmount(() => {
  emitter.off(`update:${self.value.id}`);
});

const processUpdate = async (data) => {
  if (!data) return;

  if (data?.only) {
    for (const type of data.only) {
      if (type === 1) await refetchSelf();
      if (type === 2) await fetchChildren();
    }
  } else {
    await refetchSelf();
    await fetchChildren();
  }

  if (data.forceOpen) {
    isOpen.value = true;
  } else if (children.value.length === 0) {
    isOpen.value = false;
  }
};

const isFetchingData = ref(false);
const refetchSelf = async () => {
  try {
    isFetchingData.value = true;
    const id = self.value.id;
    const response = await axios.get(`/api/groups/${id}`);
    self.value = response.data.data;
  } catch (e) {
    alert('Error refetching self');
  } finally {
    isFetchingData.value = false;
  }
};

const children = ref([]);
const alreadyFetchedChildren = ref(false);
const fetchChildren = async () => {
  try {
    isFetchingData.value = true;
    const id = self.value.id;
    const response = await axios.get(`/api/groups/${id}/children`);
    children.value = response.data.data;
    alreadyFetchedChildren.value = true
  } catch (e) {
    alert('Error fetching children');
  } finally {
    isFetchingData.value = false;
  }
};

const draggedOver = ref(false);

function handleDragOver(event) {
  event.preventDefault();
  draggedOver.value = true;
}

function handleDragLeave() {
  draggedOver.value = false;
}

const isOpen = ref(false);

// Toggle child visibility
async function toggle() {
  if (self.value.has_children) {
    if (!isOpen.value) {
      if (!alreadyFetchedChildren.value) {
        await fetchChildren();
      }
    }

    isOpen.value = !isOpen.value;
  }
}

// Drag-and-drop handlers
function onDragStart(event) {
  event.dataTransfer.setData('application/json', JSON.stringify(self.value));
}

function onDrop(event) {
  const draggedData = JSON.parse(event.dataTransfer.getData('application/json'));

  mouseUpMainCanvas();
  handleDragLeave();
  // Prevent dropping onto self
  if (draggedData.id === self.value.id) return;

  moveGroup({
    childId: draggedData.id,
    newParentId: self.value.id,
    oldParentId: draggedData.parent_group_id
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
