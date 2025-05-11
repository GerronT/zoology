<template>
  <div class="max-w-xl mx-auto p-6 bg-gray-100 rounded-lg shadow-md my-6">
    <h2 class="text-center text-xl font-bold mb-6">Add Animal</h2>
    <form @submit.prevent="submitAnimal" class="animal-form">
      <!-- Animal Name and Alternate Name -->
      <div class="flex gap-4 mb-6">
        <div class="w-full">
          <label for="name" class="block font-semibold mb-1">Animal Name</label>
          <input id="name" v-model="form.name" required placeholder="Enter animal name" class="w-full p-2 border border-gray-300 rounded-md"/>
        </div>
        <div class="w-full">
          <label for="alt_name" class="block font-semibold mb-1">Alternate Name</label>
          <input id="alt_name" v-model="form.alt_name" placeholder="Enter alternate name (optional)" class="w-full p-2 border border-gray-300 rounded-md"/>
        </div>
      </div>

      <!-- Animal Description Field -->
      <div class="mb-6">
        <label for="description" class="block font-semibold mb-1">Animal Description</label>
        <textarea id="description" v-model="form.description" placeholder="Describe the animal (optional)" rows="4" class="w-full p-2 border border-gray-300 rounded-md resize-y"></textarea>
      </div>

      <!-- Groupings section -->
      <div v-for="(group, index) in form.groupings" :key="index" class="bg-white p-4 mb-6 border-l-4 border-green-500 rounded-md">
        <!-- Toggle -->
        <div class="flex items-center justify-between mb-4">
          <label class="font-semibold">
            {{ group.useNewGroup ? 'Create new' : 'Select existing' }} group
          </label>
          <button v-if="isGroupEditable(index)" type="button" class="text-sm text-blue-600 hover:underline" @click="() => switchGroupSelectNew(group)">
            {{ group.useNewGroup ? '🔁 Switch to existing group' : '➕ Create new group' }}
          </button>
        </div>

        <!-- Existing Group Dropdown -->
        <div v-if="!group.useNewGroup" class="mb-6">
          <vue-select :v-model="group.id" :options="filteredSearchGroups(index)" @search="(query) => onSearchGroup(query, index)" :get-option-label="getGroupLabel" :disabled="!isGroupEditable(index)" placeholder="Search Group..." class="child-group-select" @update:modelValue="(val) => updateGroupId(group, val)">
            <template #no-options>
              <span v-if="index == 0">
                <span v-if="groupSearchQuery.length > 0">🔍 No groups found. Try a different search.</span>
                <span v-else>⌨️ Start searching for existing groups...</span>
              </span>
              <span v-else>🔍 No other subgroups found</span>
            </template>
          </vue-select>
        </div>

        <!-- New Group Form -->
        <div v-if="group.useNewGroup">
          <!-- Group Name -->
          <label class="block font-semibold mb-1">Group Name</label>
          <input v-model="group.name" required :disabled="!isGroupEditable(index)" placeholder="Enter group name" class="w-full p-2 border border-gray-300 rounded-md mb-4"/>

          <!-- Clade Toggle Switch -->
          <div class="flex items-center gap-2 mb-4 flex-row-reverse">
            <label class="inline-flex relative items-center cursor-pointer">
              <input type="checkbox" v-model="group.is_clade" @change="(e) => cladeGroupToggle(group, e.target.checked)" class="sr-only peer" :disabled="!isGroupEditable(index)">
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 rounded-full peer peer-checked:bg-green-500 transition-all duration-300"></div>
              <div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-all duration-300 transform peer-checked:peer-checked:translate-x-[130%]"></div>
            </label>
            <label for="clade-toggle" class="font-semibold">Unranked Clade</label>
          </div>
  
          <div v-if="!group.is_clade">
            <!-- Classification -->
            <label class="block font-semibold mb-1">Classification</label>
            <select v-model="group.classification_id" :disabled="!isGroupEditable(index)" class="w-full p-2 border border-gray-300 rounded-md mb-4">
              <option disabled :value="null">Select classification</option>
              <option v-for="c in filteredClassifications(index)" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>

            <!-- Level -->
            <label class="block font-semibold mb-1">Level</label>
            <select v-model="group.level_id" :disabled="!isGroupEditable(index) || !group.classification_id" class="w-full p-2 border border-gray-300 rounded-md mb-4">
              <option disabled :value="null">Select level</option>
              <option v-for="l in filteredLevels(index, group.classification_id)" :key="l.id" :value="l.id">{{ l.name }}</option>
            </select>
          </div>

          <!-- Description -->
          <label class="block font-semibold mb-1">Description</label>
          <input v-model="group.description" :disabled="!isGroupEditable(index)" placeholder="Describe the group (optional)" class="w-full p-2 border border-gray-300 rounded-md"/>
        </div>

        <!-- Remove button -->
        <div class="text-right mt-4" v-if="index > 0 && isGroupEditable(index)">
          <button type="button" class="px-4 py-2 bg-red-500 text-white rounded-md" @click="removeGroup(index)">
            Remove
          </button>
        </div>
      </div>

      <!-- Actions: Add Subgroup and Save Animal buttons -->
      <div class="flex justify-between gap-2 mt-6">
        <button type="button" class="px-4 py-2 bg-green-500 text-white rounded-md disabled:bg-green-300 disabled:cursor-not-allowed" @click="addGroup" :disabled="!isAddSubgroupEnabled">
          ➕ Add Subgroup
        </button>

        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-md disabled:bg-gray-400 disabled:cursor-not-allowed" :disabled="!isSaveAnimalEnabled">
          💾 Save Animal
        </button>
      </div>

    </form>
  </div>
</template>

<script>
import { reactive, computed, ref, watch } from 'vue';
import axios from 'axios';
import debounce from 'lodash.debounce';
import VueSelect from 'vue3-select'; // Import vue3-select for Vue 3
import 'vue3-select/dist/vue3-select.css';

export default {
  props: {
    classifications: Array,
    levels: Array,
  },
  components: {
    VueSelect,
  },
  setup(props) {
    const form = reactive({
      name: '',
      alt_name: '',
      description: '',
      groupings: [{id: null, name: '', classification_id: null, level_id: 5, description: '', is_clade: false , useNewGroup: false}]
    });

    const buildRankedList = (linkedList) => {
      // Create a map for easy lookup by id
      const keyedByIds = Object.fromEntries(linkedList.map(l => [l.id, l]));
      
      // Find the item with the lowest preceded_by_id value
      const root = linkedList.reduce((min, current) => {
        return current.preceded_by_id < min.preceded_by_id ? current : min;
      });
      
      const map = new Map();
      let current = root;
      let rank = 1; // top rank
      
      // Traverse the list from the root and assign ranks
      while (current) {
        map.set(current.id, rank);
        current = keyedByIds[current.succeeded_by_id];
        rank++;
      }

      return map;
    };

    // Create rank maps for classifications and levels
    const classificationRanks = computed(() => {
      return buildRankedList(props.classifications);
    });

    const levelRanks = computed(() => {
      return buildRankedList(props.levels);
    });

    // Rank functions
    const getFirstRankedId = (rankings) => {
      for (const [id, rank] of rankings.entries()) {
        if (rank === 1) return id;
      }
      return null;
    };

    const isLastRanked = (id, rankings) => {
      const maxRank = Math.max(...rankings.values());
      return rankings.get(id) === maxRank;
    };

    const getNextRankedId = (id, rankings) => {
      const currentRank = rankings.get(id);
      if (!currentRank) return null;

      for (const [otherId, rank] of rankings.entries()) {
        if (rank === currentRank + 1) {
          return otherId;
        }
      }

      return null;
    };

    const switchGroupSelectNew = (group) => {
      group.useNewGroup = !group.useNewGroup;
      console.log(group.useNewGroup);
      if (group.useNewGroup) {
        group.id = null;
      } else {
        group.name = '';
        group.classification_id = null;
        group.level_id = 5;
        group.description = '';
        group.is_clade = false;
      }
    }

    const cladeGroupToggle = (group, isOn) => {
      if (isOn) {
        group.classification_id = null;
        group.level_id = null;
      } else {
        group.level_id = 5;
      }
    }

    // Collect preselected groups for quick search
    const preselectedGroups = ref([]);

    const removeGroupFromPreselected = (group) => {
      if (group?.id) {
        const index = preselectedGroups.value.findIndex(g => g.id == group.id)
        if (index !== -1) {
          preselectedGroups.value.splice(index, 1);
        }
      }
    }

    const addGroupToPreselected = (group) => {
      if (group && !preselectedGroups.value.some(g => g.id === group.id)) {
        preselectedGroups.value.push(group);
      }
    }

    const fetchGroupValues = (group) => {
      if (!group) return null;
      return !group.useNewGroup ? (group.id ? preselectedGroups.value.find(g => g.id === group.id) : group) : group;
    }

    // Get active and/or previous groups
    const activeGroup = computed(() => {
      const group = form.groupings.at(-1);
      return fetchGroupValues(group);
    });

    const previousGroup = (index) => {
      const group = index > 0 ? form.groupings.at(index - 1) : null;
      return fetchGroupValues(group);
    };

    // Add a new subgroup to the form
    const addGroup = () => {
      const actGroup = activeGroup.value;
      form.groupings.push({id: null, name: '', classification_id: null, level_id: 5, description: '', is_clade: false, useNewGroup: actGroup?.useNewGroup ?? false});
    };

    // Remove a group from the form
    const removeGroup = (index) => {
      const removedGroup = form.groupings[index];
      removeGroupFromPreselected(removedGroup);

      form.groupings.splice(index, 1);
    };

    // Determine if the group is editable (only the last group should be editable)
    const isGroupEditable = (index) => index === form.groupings.length - 1;

    // Wait for search query prompt on very first group dropdown selection
    const groupSearchQuery = ref([]);
    const mainGroupOptions = ref([]);

    const onSearchGroup = debounce(async (query, index) => {
      groupSearchQuery.value[index] = query;

      if (!query.trim()) {
        mainGroupOptions.value[index] = [];
        return;
      }

      try {
        const response = await axios.get('/api/groups/search', {
          params: { query }
        });

        mainGroupOptions.value[index] = response.data;
      } catch (error) {
        console.error('Error fetching groups:', error);
        mainGroupOptions.value[index] = [];
      }
    }, 300);

    // Group dropdown for subgroups following the first one should be the children of the previous selected group (if selected from a dropdown)
    const childGroupOptions = ref([]);

    const fetchChildGroups = async (parentId, index) => {
      if (!parentId) {
        childGroupOptions.value[index] = [];
        return;
      }

      try {
        const response = await axios.get(`/api/groups/${parentId}/children`);
        childGroupOptions.value[index] = response.data;
      } catch (error) {
        console.error(`Error fetching children for group ${parentId}`, error);
        childGroupOptions.value[index] = [];
      }
    };

    const filteredSearchGroups = (index) => {
      if (index === 0) {
         return mainGroupOptions.value[index];
      } else {
        const mainOptions = mainGroupOptions.value?.[index] ?? [];
        const childOptions = childGroupOptions.value?.[index] ?? [];
        const mergedOptions = [...mainOptions, ...childOptions];

        return Array.from(
          new Map(mergedOptions.map(item => [item.id, item])).values()
        );
      }
    };

    const getGroupLabel = (group) => {
      const classification = props.classifications.find(c => c.id === group.classification_id);
      const level = props.levels.find(l => l.id === group.level_id);

      return `${group.name} - ${classification && level ? classification.name + ' - ' + level.name : '(Unranked Clade)'}`;
    };

    const updateGroupId = (currentGroup, newGroup) => {
      removeGroupFromPreselected(currentGroup);
      addGroupToPreselected(newGroup);

      currentGroup.id = newGroup?.id || null;
    };

    // Filter classifications selection based on previous group info
    const filteredClassifications = (index) => {
      var prevGroup = previousGroup(index);
      while (prevGroup) {
        if (!prevGroup.useNewGroup || !prevGroup.is_clade) {
          const nextClassRankId = getNextRankedId(prevGroup.classification_id, classificationRanks.value);

          return props.classifications.filter(c => {
            if (nextClassRankId && nextClassRankId == c.id) return true;
            if (!isLastRanked(prevGroup.level_id, levelRanks.value) && c.id === prevGroup.classification_id) return true;
    
            return false;
          });
        }
        prevGroup = previousGroup(index--);
      }

      const rankedFirstClassId = getFirstRankedId(classificationRanks.value);

      return props.classifications.filter(c => {
        return c.id == rankedFirstClassId;
      });
    };

    // Filter levels selection based on previous group info
    const filteredLevels = (index, classificationId) => {
      var prevGroup = previousGroup(index);
      while (prevGroup) {
        if (!prevGroup.useNewGroup || !prevGroup.is_clade) {
          if (prevGroup.classification_id == classificationId) {
            const nextLevelRankId = getNextRankedId(prevGroup.level_id, levelRanks.value);
            return props.levels.filter(l => {
              return nextLevelRankId == l.id;
            });
          }
        }
        prevGroup = previousGroup(index--);
      }

      return props.levels;
    };

    // Computed logic for enabling the 'Add Subgroup' button
    const isAddSubgroupEnabled = computed(() => {
      const actGroup = activeGroup.value;

      if (actGroup) {
        const requiredFieldsAreFilled = actGroup.useNewGroup ? actGroup.name && (actGroup.is_clade || actGroup.classification_id && actGroup.level_id) : actGroup.id;
        if (!requiredFieldsAreFilled) return false;

        const lastClassLevelComboSelected = isLastRanked(actGroup.classification_id, classificationRanks.value) && isLastRanked(actGroup.level_id, levelRanks.value);
        if (lastClassLevelComboSelected) return false;
      }

      return true;
    });

    // Computed logic for enabling the 'Save Animal' button
    const isSaveAnimalEnabled = computed(() => {
      const actGroup = activeGroup.value;

      if (actGroup) {
        const requiredFieldsAreFilled = actGroup.useNewGroup ? actGroup.name && (actGroup.is_clade || actGroup.classification_id && actGroup.level_id) : actGroup.id;
        if (!requiredFieldsAreFilled) return false;

        const lastClassSelected = isLastRanked(actGroup.classification_id, classificationRanks.value);
        if (!lastClassSelected) return false;

        const preselectedActiveGroup = actGroup.id;
        if (preselectedActiveGroup) return false;

        return true;
      }

      return false;
    });

    // Submit the animal form
    const submitAnimal = async () => {
      try {
        await axios.post('/animals', {
          ...form
        });
        alert('Animal added!');
        location.reload();
      } catch (e) {
        alert('Error creating animal');
      }
    };

    watch(() => form.groupings.map((group, i) => ({
        parentId: previousGroup(i)?.id,
        classificationId: group.classification_id
      })),
      (newVals, oldVals = []) => {
        newVals.forEach((newVal, i) => {
          const oldVal = oldVals[i] || {};
          const group = form.groupings[i];

          // Fetch children groups for group dropdown based on previous group's parent_group_id when appropriate
          if (newVal.parentId !== oldVal.parentId) {
            fetchChildGroups(newVal.parentId, i);
          }

          // Resets level_id to null if classification_id is changed to something that can no longer accomodate the level_id
          if (newVal.classificationId !== oldVal.classificationId) {
            const levels = filteredLevels(i, newVal.classificationId);
            const isCurrentLevelStillValid = levels.some(l => l.id === group.level_id);
            if (!isCurrentLevelStillValid) {
              group.level_id = null;
            }
          }
        });
      }
    );

    return {
      form,

      switchGroupSelectNew,
      cladeGroupToggle,
      addGroup,
      removeGroup,
      isGroupEditable,
      
      groupSearchQuery,
      filteredSearchGroups,
      onSearchGroup,
      getGroupLabel,
      updateGroupId,
      filteredClassifications,
      filteredLevels,

      isAddSubgroupEnabled,
      isSaveAnimalEnabled,

      submitAnimal,
    };
  },
};
</script>

