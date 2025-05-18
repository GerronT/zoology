<template>
  <div id="mainCanvas" class="canvas-container relative w-full h-screen overflow-auto bg-gray-600 overflow-hidden" @mousedown="startDragCanvas" @mousemove="dragCanvas" @mouseup="stopDragCanvas" @mouseleave="stopDragCanvas">
    <svg class="absolute top-0 left-0 z-0 w-full h-full pointer-events-none">
      <!-- Vertical gridlines -->
      <line v-for="x in gridLinesX" :key="'x-' + x" :x1="x" :y1="0" :x2="x" :y2="canvasHeight" stroke="#D1D5DB" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" stroke-opacity="0.4" />
      <!-- Horizontal gridlines -->
      <line v-for="y in gridLinesY" :key="'y-' + y" :x1="0" :y1="y" :x2="canvasWidth" :y2="y" stroke="#D1D5DB" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" stroke-opacity="0.4" />
    </svg>

    <!-- Groups display -->
    <div class="absolute flex flex-col gap-4" :style="{ left: `${canvasPosition.x}px`, top: `${canvasPosition.y}px` }">
      <div class="mt-8 ml-8">
        
        <div v-for="group in treeData" :key="group.id">
          <GroupTree :key="group.id" :node="group" @reassign-parent="handleReassignParent" @open-modal="(data) => openModal(data)" @delete-modal="(id) => openDeleteConfirmationModal(id)" />
        </div>
        
      </div>
    </div>

    <!-- Add/Edit Group Modal -->
    <GroupFormModal @recordUpdate="recordUpdate" :visible="showModal" :classifications="classifications" :levels="levels" :data="modalData" @close="closeModal"/>

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal :visible="showDeleteConfirmationModal" @close="closeDeleteConfirmationModal" :inProgress="isDeleting">
      <template #header>
        <h2 class="text-lg font-bold mb-6 text-center">Group Removal</h2>
      </template>

      <template #body>
        <p class="text-center my-8 text-sm">Selecting <span class="font-bold text-red-400 italic">`Remove Group`</span> will transfer the child groups to its parent. If you wish to delete this group along with all its descendants, please choose <span class="font-bold text-red-800 italic">'Exterminate Group'</span> instead.</p>
      </template>

      <template #extraButtons>
        <div class="flex items-end gap-2 mt-6">
          <button @click="() => deleteGroup(false)" type="submit" class="px-4 py-2 bg-red-400 enabled:hover:brightness-75 text-white rounded-md disabled:bg-red-100 disabled:cursor-not-allowed" :disabled="isDeleting">
            Remove Group
          </button>
          <button @click="() => deleteGroup(true)" type="submit" class="flex items-center gap-1 pl-2 pr-4 py-2 bg-red-800 enabled:hover:brightness-75 text-white rounded-md disabled:bg-red-200 disabled:cursor-not-allowed" :disabled="isDeleting">
            <span>⚠️</span><span>Exterminate Group</span>
          </button>
        </div>
      </template>
    </ConfirmationModal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, nextTick, provide } from 'vue';
import axios from 'axios';
import GroupTree from '../../Components/Groups/GroupTree.vue';
import GroupFormModal from '../../Components/Modals/GroupFormModal.vue';
import ConfirmationModal from '../../Components/Modals/ConfirmationModal.vue';

const props = defineProps({
  classifications: Array,
  levels: Array,
  group_root_id: Number,
});

const updateStack = ref([])

const recordUpdate = (data) => {
  updateStack.value.push(data);
}

const shiftUpdate = () => {
  updateStack.value.shift();
}

provide('updateStack', updateStack);
provide('shiftUpdate', shiftUpdate);

const showModal = ref(false);
const modalData = ref({});

const openModal = (data) => {
  modalData.value = data;
  showModal.value = true;
}

const closeModal = () => {
  showModal.value = false;
}

const showDeleteConfirmationModal = ref(false);
const groupToDelete = ref(null);
const openDeleteConfirmationModal = (data) => {
  groupToDelete.value = data;
  showDeleteConfirmationModal.value = true;
}
const closeDeleteConfirmationModal = () => {
  groupToDelete.value = null;
  showDeleteConfirmationModal.value = false;
}

const isDeleting = ref(false);
const deleteGroup = async (exterminate = false) => {
  isDeleting.value = true;
  try {
    const id = groupToDelete.value?.id;
    const parent_group_id = groupToDelete.value?.parent_group_id;
    await axios.delete(`/groups/${id}`, {
      data: {
        exterminate: exterminate
      },
    });
    alert('Group deleted!');
    recordUpdate({type: 'delete', group_id: parent_group_id})
  } catch (e) {
    alert('Error deleted group');
  }
  isDeleting.value = false;
  closeDeleteConfirmationModal();
};

const treeData = ref([]);
const loading = ref(true);

// For dragging canvas (X, Y position)
const canvasPosition = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const startMousePosition = ref({ x: 0, y: 0 });

// Store the lines (connecting lines between nodes)
const lines = ref([]);

// Canvas size (for grid calculations)
const canvasWidth = ref(0);
const canvasHeight = ref(0);

// Define grid spacing (adjust this value to control the grid spacing)
const gridSpacing = 50;

const updateCanvasSize = async () => {
  await nextTick();
  const container = document.querySelector('.canvas-container'); // Add a class to your group div
  if (container) {
    const rect = container.getBoundingClientRect();
    canvasWidth.value = Math.ceil(rect.right);
    canvasHeight.value = Math.ceil(rect.bottom);
  }
};

const gridLinesX = computed(() => {
  const lines = [];
  for (let i = 0; i < canvasWidth.value; i += gridSpacing) {
    lines.push(i);
  }
  return lines;
});

const gridLinesY = computed(() => {
  const lines = [];
  for (let i = 0; i < canvasHeight.value; i += gridSpacing) {
    lines.push(i);
  }
  return lines;
});


onMounted(async () => {
  try {
    const response = await axios.get('/api/groups/tree', {
      params: {
        group_root_id: props.group_root_id
      },
    });
    treeData.value = response.data.data;
    await updateCanvasSize()
    window.addEventListener('resize', detectZoomChange);
  } catch (err) {
    console.error('Error loading tree:', err);
  } finally {
    loading.value = false;
  }
});

// Start dragging the canvas
const startDragCanvas = (event) => {
  isDragging.value = true;
  startMousePosition.value = { x: event.clientX, y: event.clientY };
};

// Handle dragging the canvas
const dragCanvas = (event) => {
  if (!isDragging.value) return;
  const dx = event.clientX - startMousePosition.value.x;
  const dy = event.clientY - startMousePosition.value.y;
  canvasPosition.value.x += dx;
  canvasPosition.value.y += dy;
  startMousePosition.value = { x: event.clientX, y: event.clientY };
};

// Stop dragging
const stopDragCanvas = (event) => {
  isDragging.value = false;
};

const handleReassignParent = async ({ childId, newParentId, oldParentId }) => {
  try {
    await axios.post('/api/move-group', {
      child_id: childId,
      new_parent_id: newParentId
    });
    recordUpdate({type: 'move', group_id: oldParentId});
    recordUpdate({type: 'move', group_id: newParentId, forceOpen: true});
  } catch (err) {
    if (err.response && err.response.data) {
      console.error('Backend error:', err.response.data);
      alert(err.response.data.message || 'Failed to update parent group.');
    } else {
      console.error('Unknown error:', err);
      alert('An unexpected error occurred.');
    }
  }
};

const previousRatio = ref(window.devicePixelRatio);

const detectZoomChange = () => {
  if (window.devicePixelRatio !== previousRatio.value) {
    previousRatio.value = window.devicePixelRatio;
    updateCanvasSize();
  }
};




</script>

<style scoped>
.canvas-container {
  cursor: grab;
}

.canvas-container:active {
  cursor: grabbing;
}
</style>
