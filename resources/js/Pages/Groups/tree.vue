<template>
  <div
    id="mainCanvas"
    class="canvas-container relative w-full h-screen overflow-auto bg-gray-600"
    @mousedown="startDragCanvas"
    @mousemove="dragCanvas"
    @mouseup="stopDragCanvas"
    @mouseleave="stopDragCanvas"
  >
    <!-- SVG for gridlines -->
    <svg class="absolute top-0 left-0 z-0 w-full h-full pointer-events-none">
      <!-- Vertical gridlines -->
      <line
        v-for="x in gridLinesX"
        :key="'x-' + x"
        :x1="x"
        :y1="0"
        :x2="x"
        :y2="canvasHeight"
        stroke="#D1D5DB"
        stroke-width="1"
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-opacity="0.4"
      />
      <!-- Horizontal gridlines -->
      <line
        v-for="y in gridLinesY"
        :key="'y-' + y"
        :x1="0"
        :y1="y"
        :x2="canvasWidth"
        :y2="y"
        stroke="#D1D5DB"
        stroke-width="1"
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-opacity="0.4"
      />
    </svg>

    <!-- Groups display -->
    <div
      class="absolute flex flex-col gap-4"
      :style="{ left: `${canvasPosition.x}px`, top: `${canvasPosition.y}px` }"
    >
      <div class="mt-8 ml-8">
        <GroupTree 
          v-for="group in treeData"
          :key="group.id"
          :node="group"
          @add-line="addLine"
          @reassign-parent="handleReassignParent"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import GroupTree from '../../Components/Groups/GroupTree.vue';

const treeData = ref([]);
const loading = ref(true);

// For dragging canvas (X, Y position)
const canvasPosition = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const startMousePosition = ref({ x: 0, y: 0 });

// Store the lines (connecting lines between nodes)
const lines = ref([]);

// Canvas size (for grid calculations)
const canvasWidth = 2000; // Set the width of the canvas (adjustable)
const canvasHeight = 2000; // Set the height of the canvas (adjustable)

// Define grid spacing (adjust this value to control the grid spacing)
const gridSpacing = 100;

// Computed property for vertical gridlines
const gridLinesX = computed(() => {
  const lines = [];
  for (let i = 0; i < canvasWidth; i += gridSpacing) {
    lines.push(i);
  }
  return lines;
});

// Computed property for horizontal gridlines
const gridLinesY = computed(() => {
  const lines = [];
  for (let i = 0; i < canvasHeight; i += gridSpacing) {
    lines.push(i);
  }
  return lines;
});

onMounted(async () => {
  try {
    const response = await axios.get('/api/group-tree');
    treeData.value = response.data.data;
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

// Add a line connecting two nodes
const addLine = (line) => {
  lines.value.push(line);
};

const handleReassignParent = async ({ childId, newParentId }) => {
  try {
    await axios.post('/api/move-group', {
      child_id: childId,
      new_parent_id: newParentId
    });
    const response = await axios.get('/api/group-tree');
    treeData.value = response.data.data;
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

</script>

<style scoped>
.canvas-container {
  cursor: grab;
}

.canvas-container:active {
  cursor: grabbing;
}
</style>
