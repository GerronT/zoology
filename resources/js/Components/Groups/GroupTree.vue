<template>
  <ul class="relative inline-flex">
    <span class="rounded-full" :class="self.is_root ? 'bg-red-700 w-5 h-5 mt-4' : 'w-3 h-3 bg-yellow-500 mt-5 -ml-[5px]'"></span>
    <div class="border-t-2 border-orange-300 mt-6 w-8 border-dashed"></div>
    <li class="cursor-move relative before:absolute before:left-6 before:border-l-2 before:border-orange-300 mt-2 before:top-0 before:h-full before:border-dotted" :id="`node-${self.id}`">
      <div class="relative inline-flex items-center">
          <div class="relative inline-flex items-center gap-1 border-2 rounded-md px-2 py-1 cursor-pointer hover:brightness-75" draggable="true" @dragstart="onDragStart" @dragover.prevent="handleDragOver" @dragleave="handleDragLeave" @drop="onDrop" @click="toggle" @mouseup="(e) => stopDragCanvas()" @mouseleave="(e) => stopDragCanvas()" :class="`bg-${self.primary_class} border-orange-300` + (draggedOver ? ` brightness-200` : ``) + (isFetchingData ? ' cursor-wait' : ' cursor-pointer')">
            <span v-if="self.has_children" class="w-4 text-xs text-orange-500">{{ isOpen ? '▼' : '▶' }}</span>
            <strong class="text-sm text-gray-800">{{ self.name }}</strong>
            <span v-if="self.classification" class="text-xs text-gray-500">({{ self.classification }})</span>
            <span v-if="self.level" class="text-xs text-gray-400">- Level: {{ self.level }}</span>
            <span v-if="!self.classification && !self.level" class="text-xs text-gray-400 italic">- (Unranked Clade)</span>
          </div>

          <div class="absolute right-0 mb-8 -mr-4">
            <div class="flex items-center gap-1">
              <button @click="openAddGroupModal(self)" class="h-4 w-4 flex items-center justify-center text-[8px] font-medium text-green-800 bg-green-100 border-2 border-green-300 rounded hover:bg-green-200 transition" title="Add">➕</button>
              <button @click="openEditGroupModal(self)" class="h-4 w-4 flex items-center justify-center text-[8px] font-medium text-blue-800 bg-blue-100 border-2 border-blue-300 rounded hover:bg-blue-200 transition" title="Edit">✏️</button>
              <button @click="openDeleteConfirmationModal(self)" class="h-4 w-4 flex items-center justify-center text-[8px] font-medium text-red-800 bg-red-100 border-2 border-red-300 rounded hover:bg-red-200 transition" title="Delete">🗑️</button>
            </div>
          </div>
      </div>

      <!-- Animals list -->
      <ul v-show="self.animals?.length" class="ml-6 flex flex-row">
        <span class="rounded-full w-3 h-3 bg-purple-400 mt-4 -ml-[5px] relative"></span>
        <div class="border-t-2 border-purple-300 mt-5 w-8"></div>
        <li v-for="animal in self.animals" :key="animal.id" class="cursor-move text-sm text-gray-600 italic bg-purple-100 hover:bg-purple-200 border-2 border-purple-300 p-1 rounded-md mt-2 pr-4">
          🐾 {{ animal.name }} <span v-if="animal.alt_name">({{ animal.alt_name }})</span>
        </li>
      </ul>

      <!-- Children (recursive) -->
      <transition enter-active-class="transition duration-300 ease-out" leave-active-class="transition duration-200 ease-in" enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-[1000px]" leave-from-class="opacity-100 max-h-[1000px]" leave-to-class="opacity-0 max-h-0">
        <ul v-show="isOpen && self.has_children" class="ml-6 mt-2 space-y-2 flex flex-col">
          <group-tree v-for="child in children" :key="child.id" :node="child" />
        </ul>
      </transition>
    </li>
  </ul>
</template>

<script>
  import { ref, watch, inject, onMounted, onBeforeUnmount } from 'vue';

  export default {
    props: {
      node: {
        type: Object,
        required: true
      }
    },
    setup(props) {
      // Define injections
      const emitter = inject('emitter');
      const openAddGroupModal = inject('openAddGroupModal');
      const openEditGroupModal = inject('openEditGroupModal');
      const openDeleteConfirmationModal = inject('openDeleteConfirmationModal');
      const moveGroup = inject('moveGroup');
      const stopDragCanvas = inject('stopDragCanvas');

      // Pull node data from props so we can perform data updates
      const self = ref({ ...props.node });
      watch(() => props.node, (newData) => {
        self.value = { ...newData };
      });

      // Perform data updates for each nodes (Previously done through watch)
      onMounted(() => {
        emitter.on(`update:${self.value.id}`, async (data) => {
          await processUpdate(data);
          if (typeof data.done === 'function') {
            data.done(); // Notify the queue to continue
          }
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

        if (data?.forceOpen) {
          isOpen.value = true;
        }
        
        if (children.value.length === 0) {
          isOpen.value = false;
        }
      };

      // Refetch data for this node (group)
      const isFetchingData = ref(false);
      const refetchSelf = async () => {
        try {
          isFetchingData.value = true;
          const id = self.value.id;
          const response = await axios.get(`/groups/${id}`);
          self.value = response.data.data;
        } catch (e) {
          alert('Error refetching self');
        } finally {
          isFetchingData.value = false;
        }
      };

      // Fetch node's (group's) child groups
      const children = ref([]);
      const alreadyFetchedChildren = ref(false);
      const fetchChildren = async () => {
        try {
          isFetchingData.value = true;
          const id = self.value.id;
          const response = await axios.get(`/groups/${id}/children`);
          children.value = response.data.data;
          alreadyFetchedChildren.value = true
        } catch (e) {
          alert('Error fetching children');
        } finally {
          isFetchingData.value = false;
        }
      };

      // Toggle child groups visibility
      const isOpen = ref(false);
      const toggle = async() => {
        if (self.value.has_children) {
          if (!isOpen.value) {
            if (!alreadyFetchedChildren.value) {
              await fetchChildren();
            }
          }
          isOpen.value = !isOpen.value;
        }
      }

      const updateOpenNodes = inject('updateOpenNodes');
      watch(() => isOpen.value, (isOn) => {
        updateOpenNodes(self.value.id, isOn);
      });

      // Drag-and-drop handlers
      const onDragStart = (event) => {
        event.dataTransfer.setData('application/json', JSON.stringify(self.value));
      }

      const draggedOver = ref(false);

      const handleDragOver = (event) => {
        event.preventDefault();
        draggedOver.value = true;
      }

      const handleDragLeave = () => {
        draggedOver.value = false;
      }

      const onDrop = (event) => {
        const draggedData = JSON.parse(event.dataTransfer.getData('application/json'));

        stopDragCanvas(); // Stop drag on canvas as well
        handleDragLeave();
        
        if (draggedData.id === self.value.id) return; // Prevent dropping onto self

        moveGroup({
          childId: draggedData.id,
          newParentId: self.value.id,
          oldParentId: draggedData.parent_group_id
        });
      }

      return {
        self,
        children,

        openAddGroupModal,
        openEditGroupModal,
        openDeleteConfirmationModal,

        onDragStart,
        handleDragOver,
        handleDragLeave,

        stopDragCanvas,
        draggedOver,
        onDrop,

        isFetchingData,

        isOpen,
        toggle,
      }
    }
  }

</script>
