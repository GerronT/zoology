<template>
  <div id="mainCanvas" class="canvas-container cursor-grab active:cursor-grabbing relative w-full h-screen overflow-auto bg-gray-600 overflow-hidden" @mousedown="startDragCanvas" @mousemove="dragCanvas" @mouseup="stopDragCanvas" @mouseleave="stopDragCanvas">
    <svg class="absolute top-0 left-0 z-0 w-full h-full pointer-events-none">
      <!-- Vertical gridlines -->
      <line v-for="x in gridLinesX" :key="'x-' + x" :x1="x" :y1="0" :x2="x" :y2="canvasHeight" stroke="#D1D5DB" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" stroke-opacity="0.4" />
      <!-- Horizontal gridlines -->
      <line v-for="y in gridLinesY" :key="'y-' + y" :x1="0" :y1="y" :x2="canvasWidth" :y2="y" stroke="#D1D5DB" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" stroke-opacity="0.4" />
    </svg>

    <!-- Groups displayed Recursively -->
    <div class="absolute flex flex-col gap-4" :style="{ left: `${canvasPosition.x}px`, top: `${canvasPosition.y}px` }">
      <div class="mt-8 ml-8">
        <GroupTree v-for="group in treeData" :key="group.id" :node="group" />
      </div>
    </div>

    <!-- Add Group Modal -->
    <add-group-modal @recordUpdate="recordUpdate" :visible="showAddGroupModal" :classifications="classifications" :levels="levels" :data="groupToAddTo" @close="closeAddGroupModal"/>

    <!-- Edit Group Modal -->
    <edit-group-modal @recordUpdate="recordUpdate" :visible="showEditGroupModal" :classifications="classifications" :levels="levels" :data="groupToEdit" @close="closeEditGroupModal"/>

    <!-- Delete Confirmation Modal -->
    <confirmation-modal :visible="showDeleteConfirmationModal" @close="closeDeleteConfirmationModal" :inProgress="isDeleting">
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
    </confirmation-modal>
  </div>
</template>

<script>
  import { ref, onMounted, computed, nextTick, provide } from 'vue';
  import axios from 'axios';
  import mitt from 'mitt';
  import GroupTree from '../../Components/Groups/GroupTree.vue';
  import AddGroupModal from '../../Components/Modals/AddGroupModal.vue';
  import EditGroupModal from '../../Components/Modals/EditGroupModal.vue';
  import ConfirmationModal from '../../Components/Modals/ConfirmationModal.vue';
  
  export default {
    props: {
      classifications: Array,
      levels: Array,
      group_root_id: Number,
    },
    components: {
      GroupTree,
      AddGroupModal,
      EditGroupModal,
      ConfirmationModal
    },
    setup(props) {
      const treeData = ref([]);
      const loading = ref(true); // TODO - show loading circle on template

      onMounted(async () => {
        loading.value = true;
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

      // Update canvas size - should be triggered on mounted
      const canvasWidth = ref(0);
      const canvasHeight = ref(0);

      const updateCanvasSize = async () => {
        await nextTick();
        const container = document.querySelector('.canvas-container');
        if (container) {
          const rect = container.getBoundingClientRect();
          canvasWidth.value = Math.ceil(rect.right);
          canvasHeight.value = Math.ceil(rect.bottom);
        }
      };

      // Detect zooms and update canvas size
      const previousRatio = ref(window.devicePixelRatio);
      const detectZoomChange = () => {
        if (window.devicePixelRatio !== previousRatio.value) {
          previousRatio.value = window.devicePixelRatio;
          updateCanvasSize();
        }
      };

      // Computed grid lines based on the canvas size
      const lines = ref([]);
      const gridSpacing = 50;

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

      // Handle draggable canvas
      const isDragging = ref(false);
      const canvasPosition = ref({ x: 0, y: 0 });
      const startMousePosition = ref({ x: 0, y: 0 });

      const startDragCanvas = (event) => {
        isDragging.value = true;
        startMousePosition.value = { x: event.clientX, y: event.clientY };
      };

      const dragCanvas = (event) => {
        if (!isDragging.value) return;
        const dx = event.clientX - startMousePosition.value.x;
        const dy = event.clientY - startMousePosition.value.y;
        canvasPosition.value.x += dx;
        canvasPosition.value.y += dy;
        startMousePosition.value = { x: event.clientX, y: event.clientY };
      };

      const stopDragCanvas = (event) => {
        isDragging.value = false;
      };

      // Handles triggering group updates
      const emitter = mitt();

      provide('emitter', emitter);

      const recordUpdate = (data) => {
        emitter.emit(`update:${data.group_id}`, data);
      }

      // Add Group Modal
      const showAddGroupModal = ref(false);
      const groupToAddTo = ref({});

      const openAddGroupModal = (data) => {
        groupToAddTo.value = data;
        showAddGroupModal.value = true;
      }

      provide('openAddGroupModal', openAddGroupModal);

      const closeAddGroupModal = () => {
        groupToAddTo.value = null;
        showAddGroupModal.value = false;
      }

      // Edit Group Modal
      const showEditGroupModal = ref(false);
      const groupToEdit = ref({});

      const openEditGroupModal = (data) => {
        groupToEdit.value = data;
        showEditGroupModal.value = true;
      }

      provide('openEditGroupModal', openEditGroupModal);

      const closeEditGroupModal = () => {
        groupToEdit.value = null;
        showEditGroupModal.value = false;
      }

      // Delete Confirmation Modal
      const showDeleteConfirmationModal = ref(false);
      const groupToDelete = ref(null);

      const openDeleteConfirmationModal = (data) => {
        groupToDelete.value = data;
        showDeleteConfirmationModal.value = true;
      }

      provide('openDeleteConfirmationModal', openDeleteConfirmationModal);

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
          recordUpdate({type: 'delete', group_id: parent_group_id});
          closeDeleteConfirmationModal();
        } catch (e) {
          alert('Error deleted group');
        } finally {
          isDeleting.value = false;
        }
      };

      const moveGroup = async ({ childId, newParentId, oldParentId }) => {
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

      provide('moveGroup', moveGroup);

      return {
        canvasHeight,
        canvasWidth,
        canvasPosition,

        startDragCanvas,
        dragCanvas,
        stopDragCanvas,

        gridLinesX,
        gridLinesY,

        treeData,
        recordUpdate,

        groupToAddTo,
        showAddGroupModal,
        closeAddGroupModal,

        groupToEdit,
        showEditGroupModal,
        closeEditGroupModal,

        showDeleteConfirmationModal,
        closeDeleteConfirmationModal,
        deleteGroup,
        isDeleting
      };
    }
  }
</script>

