<template>
    <div>
      <!-- Groupings section -->
      <div v-for="(group, index) in groupings" :key="index" class="bg-white p-4 mb-6 border-l-4 border-green-500 rounded-md">
        <group-item 
            :group="group" 
            :index="index"

            :groupSearchQuery="groupSearchQuery"
            :isGroupEditable="() => isGroupEditable(index)"
            :filteredSearchGroups="() => filteredSearchGroups(index)"
            :filteredClassifications="() => filteredClassifications(index)"
            :filteredLevels="() => filteredLevels(index, group.classification_id)"

            :getClassificationById="getClassificationById"
            :getLevelById="getLevelById"

            @removeGroup="() => removeGroup(index)"
            @onSearchGroup="(query) => onSearchGroup(query, index)"
            @removeGroupFromPreselected="removeGroupFromPreselected"
            @addGroupToPreselected="addGroupToPreselected"
            @update:group="(data) => emit('update:groups', index, data)"
        />
      </div>

      <!-- Actions: Add Subgroup and Save Animal buttons -->
      <div class="flex justify-between gap-2 mt-6">
        <button type="button" class="px-4 py-2 bg-green-500 text-white rounded-md disabled:bg-green-300 disabled:cursor-not-allowed" @click="addGroup" :disabled="!isAddSubgroupEnabled">
          ➕ Add Subgroup
        </button>
        <slot name="cta"></slot>
      </div>
    </div>
</template>

<script>
import { computed, ref, watch, } from 'vue';
import axios from 'axios';
import debounce from 'lodash.debounce';
import 'vue3-select/dist/vue3-select.css';
import GroupItem from './GroupItem.vue';
import { useStore } from 'vuex';

export default {
  props: {
    groupings: Array,
    classifications: Array,
    levels: Array,
  },
  components: {
    GroupItem
  },
  emits: ['addGroup', 'removeGroup', 'update:groups'],
  setup(props, { emit }) {
    const store = useStore();

    // Accessing the ranks from Vuex store
    const classificationRanks = computed(() => store.getters.getClassificationRanks);
    const levelRanks = computed(() => store.getters.getLevelRanks);

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
      const group = props.groupings.at(-1);
      return fetchGroupValues(group);
    });

    const previousGroup = (index) => {
      const group = index > 0 ? props.groupings.at(index - 1) : null;
      return fetchGroupValues(group);
    };

    // Add a new subgroup to the form
    const addGroup = () => {
      const actGroup = activeGroup.value;
      emit('addGroup', actGroup?.useNewGroup);
    };

    // Remove a group from the form
    const removeGroup = (index) => {
      const removedGroup = props.groupings[index];
      removeGroupFromPreselected(removedGroup);
      emit('removeGroup', index);
    };

    // Determine if the group is editable (only the last group should be editable)
    const isGroupEditable = (index) => index === props.groupings.length - 1;

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
        const uniqueOptions = Array.from(new Map(mergedOptions.map(item => [item.id, item])).values());
        
        const excludePreselectedGroups = preselectedGroups.value.map(g => g.id);
        const filteredOptions = uniqueOptions.filter(item => !excludePreselectedGroups.includes(item.id));

        return filteredOptions;
      }
    };

    const youngestRankedAncestorOutOfScope = ref(null);
    const fetchYoungestRankedAncestor = async (groupId) => {        
        if (!groupId) {
            youngestRankedAncestorOutOfScope.value = null;
            return;
        }
        try {
            const response = await axios.get(`/api/groups/${groupId}/youngest-ranked-ancestor`);
            youngestRankedAncestorOutOfScope.value = response.data;
        } catch (error) {
            console.error(`Error fetching youngest ranked ancestor for group ${groupId}`, error);
            youngestRankedAncestorOutOfScope.value = null;
        }
    };

    const youngestRankedAncestor = (index) => {
        var prevGroup = previousGroup(index);
        var found = false;
        while (prevGroup) {
            if (prevGroup.id && prevGroup.classification_id && prevGroup.level_id || prevGroup.useNewGroup && !prevGroup.is_clade) {
                found = true;
                break;
            }
            prevGroup = previousGroup(index--);
        }

        if (!found) {
            prevGroup = youngestRankedAncestorOutOfScope.value;
        }

        return prevGroup;
    }

    // Filter classifications selection based on previous group info
    const filteredClassifications = (index) => {
      const yngRnkAncestor = youngestRankedAncestor(index);

      if (yngRnkAncestor) {
        const nextClassRankId = getNextRankedId(yngRnkAncestor.classification_id, classificationRanks.value);
        return props.classifications.filter(c => {
            if (nextClassRankId && nextClassRankId == c.id) return true;
            if (!isLastRanked(yngRnkAncestor.level_id, levelRanks.value) && c.id === yngRnkAncestor.classification_id) return true;
            return false;
        });
      }

      const rankedFirstClassId = getFirstRankedId(classificationRanks.value);

      return props.classifications.filter(c => {
        return c.id == rankedFirstClassId;
      });
    };

    // Filter levels selection based on previous group info
    const filteredLevels = (index, classificationId) => {
      const yngRnkAncestor = youngestRankedAncestor(index);

      if (yngRnkAncestor) {
        if (yngRnkAncestor.classification_id == classificationId) {
            return props.levels.filter(l => {
                return levelRanks.value.get(l.id) > levelRanks.value.get(yngRnkAncestor.level_id);
            });
        }
      }

      return props.levels;
    };

    const getClassificationById = (classification_id) => {
        return props.classifications.find(c => c.id === classification_id);
    }

    const getLevelById = (level_id) => {
        return props.levels.find(l => l.id === level_id);
    }

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

    watch(() => props.groupings.map((group, i) => ({
        parentId: previousGroup(i)?.id,
        classificationId: group.classification_id,
        groupId: group.id
      })),
      (newVals, oldVals = []) => {
        newVals.forEach((newVal, i) => {
          const oldVal = oldVals[i] || {};
          const group = props.groupings[i];

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

          if (i == 0) {
            if (oldVals[i].groupId !== newVals[i].groupId) {
                fetchYoungestRankedAncestor(newVals[i].groupId);
            }
          }
        });
      }
    );

    const updateGroupData = (index, newData) => {
      if (group.hasOwnProperty(newData.key)) {
        group[newData.key] = newData.value;
      }
    };

    return {
      emit,

      addGroup,
      isAddSubgroupEnabled,
      
      removeGroup,
      onSearchGroup,
      removeGroupFromPreselected,
      addGroupToPreselected,

      isGroupEditable,
      groupSearchQuery,
      filteredSearchGroups,
      filteredClassifications,
      filteredLevels,
      getClassificationById,
      getLevelById,
    };
  },
};
</script>

