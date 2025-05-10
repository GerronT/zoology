<template>
  <div class="container">
    <h2 class="header">Add Animal</h2>

    <form @submit.prevent="submitAnimal" class="animal-form">

      <!-- Animal Name and Alternate Name -->
      <div class="name-row">
        <div class="form-field">
          <label for="name">Animal Name</label>
          <input
            id="name"
            v-model="form.name"
            required
            placeholder="Enter animal name"
          />
        </div>
        <div class="form-field">
          <label for="alt_name">Alternate Name</label>
          <input
            id="alt_name"
            v-model="form.alt_name"
            placeholder="Enter alternate name (optional)"
          />
        </div>
      </div>

      <!-- Description Field -->
      <div class="form-field">
        <label for="description">Animal Description</label>
        <textarea
          id="description"
          v-model="form.description"
          placeholder="Describe the animal (optional)"
          rows="4"
        ></textarea>
      </div>

      <!-- Groupings section -->
      <div
        v-for="(group, index) in form.groupings"
        :key="index"
        class="group-item"
      >

      <div class="child-group-dropdown">
          <label for="child-group">Group (optional)</label>
          <vue-select
            :modelValue="getGroupById(group.id)"
            :options="filteredChildGroups(index)"
            :get-option-label="getGroupLabel"
            :disabled="!isGroupEditable(index)"
            placeholder="Search Group..."
            class="child-group-select"
            @update:modelValue="val => group.id = val?.id"
          />
      </div>

      <div v-if="!group?.id">
          <!-- Group Name input -->
          <label>Group Name</label>
          <input
            v-model="group.name"
            required
            :disabled="!isGroupEditable(index)"
            placeholder="Enter group name"
          />

          <!-- Classification dropdown -->
          <label>Classification</label>
          <select
            v-model="group.classification_id"
            :disabled="!isGroupEditable(index)"
          >
            <option disabled :value="null">Select classification</option>
            <option
              v-for="c in filteredClassifications(index)"
              :key="c.id"
              :value="c.id"
            >
              {{ c.name }}
            </option>
          </select>

          <!-- Level dropdown -->
          <label>Level</label>
          <select
            v-model="group.level_id"
            :disabled="!isGroupEditable(index) || !group.classification_id"
          >
            <option disabled :value="null">Select level</option>
            <option
              v-for="l in filteredLevels(index, group.classification_id)"
              :key="l.id"
              :value="l.id"
            >
              {{ l.name }}
            </option>
          </select>

          <!-- Description input -->
          <label>Description</label>
          <input
            v-model="group.description"
            :disabled="!isGroupEditable(index)"
            placeholder="Describe the group (optional)"
          />
        </div>

        <!-- Remove button for group -->
        <div class="remove-button" v-if="index > 0">
          <button
            type="button"
            class="btn btn-remove"
            :disabled="!isGroupEditable(index)"
            @click="removeGroup(index)"
          >
            Remove
          </button>
        </div>
      </div>

      <!-- Actions: Add Subgroup and Save Animal buttons -->
      <div class="form-actions">
        <button
          type="button"
          class="btn btn-subgroup"
          @click="addGroup"
          :disabled="!canAddGroup || isLastCombinationSelected"
        >
          ➕ Add Subgroup
        </button>

        <button
          type="submit"
          class="btn btn-submit"
          :disabled="!isSaveAnimalEnabled"
        >
          💾 Save Animal
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import { reactive, computed, ref, watch } from 'vue';
import axios from 'axios';
import VueSelect from 'vue3-select'; // Import vue3-select for Vue 3
import 'vue3-select/dist/vue3-select.css';

export default {
  props: {
    classifications: Array,
    levels: Array,
    groups: Array,  // This will contain the list of available groups
    animals: Array,
  },
  components: {
    VueSelect,
  },
  setup(props) {
    // Reactive form data
    const form = reactive({
      name: null,
      alt_name: null,
      description: null,
      groupings: [{id: null, name: '', classification_id: null, level_id: 5, description: ''}]
    });

    // Create rank maps for classifications and levels
    const classificationRanks = computed(() => {
      const map = new Map();
      props.classifications.forEach((c, i) => map.set(c.id, i));
      return map;
    });

    const levelRanks = computed(() => {
      const map = new Map();
      props.levels.forEach((l, i) => map.set(l.id, i));
      return map;
    });

    // Calculate combo rank (classification and level combination)
    const getComboRank = (classificationId, levelId) => {
      return (
        classificationRanks.value.get(classificationId) * 100 +
        levelRanks.value.get(levelId)
      );
    };

    // Resets level_id to null if classification_id is changed to something that can no longer accomodate the level_id
    watch(() => form.groupings.map(group => group.classification_id),
      (newValues, oldValues) => {
        newValues.forEach((newClassId, index) => {
          const group = form.groupings[index];
          const levels = filteredLevels(index, newClassId);
          const isCurrentLevelStillValid = levels.some(l => l.id === group.level_id);

          if (!isCurrentLevelStillValid) {
            group.level_id = null;
          }
        });
      }
    );

    // The last group in the form
    const lastGroup = computed(() => {
      const group = form.groupings.at(-1);
      return group?.id
        ? props.groups.find(g => g.id === group.id)
        : group || null;
    });

    const previousGroup = (index) => {
      const group = index > 0 ? form.groupings.at(index - 1) : null;
      return group?.id
        ? props.groups.find(g => g.id === group.id)
        : group || null;
    };


    // Check if the last group is valid (has both classification, level and name)
    const canAddGroup = computed(() => {
      var activeGroup = lastGroup.value;
      if (activeGroup) {
        return activeGroup.id || activeGroup.classification_id && activeGroup.level_id && activeGroup.name;
      }
      return true;
    });

    const isLastCombinationSelected = computed(() => {
      const activeGroup = lastGroup.value;
      if (activeGroup) {
        if (!activeGroup.classification_id || !activeGroup.level_id) return false;
        
        const classification = props.classifications.find(c => c.id === activeGroup.classification_id);
        const level = props.levels.find(l => l.id === activeGroup.level_id);

        return classification?.succeeded_by_id === null && level?.succeeded_by_id === null;
      }
      return false;
    });

    // Determine if the group is editable (only the last group is editable)
    const isGroupEditable = (index) => index === form.groupings.length - 1;

    // Add a new group to the form
    const addGroup = () => {
      if (!canAddGroup.value) return;

      form.groupings.push({
        id: null,
        name: '',
        classification_id: null,
        level_id: 5,
        description: '',
      });
    };

    // Remove a group from the form
    const removeGroup = (index) => {
      form.groupings.splice(index, 1);
    };

    // Filter groups
    const filteredChildGroups = (index) => {
      const prevGroup = previousGroup(index);

      return props.groups.filter(g => {
        if (prevGroup?.id) {
          return g.parent_group_id == prevGroup.id;
        }
        return index == 0;
      });
    };

    // Filter classifications based on the current selection in the group
    const filteredClassifications = (index) => {
      const prevGroup = previousGroup(index);

      const prevClass = props.classifications.find(c => c.id === prevGroup?.classification_id);
      const prevLevel = props.levels.find(l => l.id === prevGroup?.level_id);

      const isLastLevel = prevLevel?.succeeded_by_id === null;
      const nextClassId = prevClass?.succeeded_by_id;

      return props.classifications.filter(c => {
        if (prevClass && prevLevel) {
          return (!isLastLevel && c.id === prevClass.id) || (c.id === nextClassId);
        }
        return c.preceded_by_id == null;
      });
    };


    // Filter levels based on the classification and group combination
    const filteredLevels = (index, classificationId) => {
      const prevGroup = previousGroup(index);

      const prevClass = props.classifications.find(c => c.id === prevGroup?.classification_id);
      const prevLevel = props.levels.find(l => l.id === prevGroup?.level_id);

      return props.levels.filter(l => {
        if (prevClass && prevLevel && prevClass.id == classificationId) {
          return levelRanks.value.get(l.id) > levelRanks.value.get(prevLevel.id);
        }
        return true;
      });
    };

    const searchQuery = ref('');
    const filteredGroups = computed(() => {
      if (!searchQuery.value.trim()) return [];
      const lowerQuery = searchQuery.value.toLowerCase();
      return props.groups.filter(group =>
        group.name.toLowerCase().includes(lowerQuery)
      );
    });

    const onSearchGroup = (query) => {
      searchQuery.value = query;
    };

    const getGroupLabel = (group) => {
      const classification = props.classifications.find(c => c.id === group.classification_id);
      const level = props.levels.find(l => l.id === group.level_id);
      return `${group.name} - ${classification?.name ?? ''} - ${level?.name ?? ''}`;
    };

    // Update logic for enabling the save button
    const isSaveAnimalEnabled = computed(() => {
      const activeGroup = lastGroup.value;

      var mainGroupUsesLastClassAndNotYetAssignedToAnimal = false;
      if (activeGroup?.classification_id && activeGroup?.level_id) {
        const classification = props.classifications.find(c => c.id === activeGroup.classification_id);
        const assignedAnimal = props.animals.find(a => a.group_id == activeGroup?.id);
        mainGroupUsesLastClassAndNotYetAssignedToAnimal = classification?.succeeded_by_id === null && !assignedAnimal; 
      }

      return mainGroupUsesLastClassAndNotYetAssignedToAnimal && form.name;
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

    const getGroupById = (id) => {
      return props.groups.find(g => g.id === id) || null;
    };

    return {
      form,
      addGroup,
      removeGroup,
      canAddGroup,
      isLastCombinationSelected,
      isGroupEditable,
      filteredChildGroups,
      filteredClassifications,
      filteredLevels,
      isSaveAnimalEnabled,
      submitAnimal,
      filteredGroups,
      onSearchGroup,
      getGroupLabel,
      getGroupById
    };
  },
};
</script>

<style scoped>
.container {
  max-width: 600px;
  margin: auto;
  padding: 1.5rem;
  background-color: #f8f8f8;
  border-radius: 6px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.header {
  text-align: center;
  margin-bottom: 1.5rem;
  font-size: 1.5rem;
  font-weight: bold;
}

.animal-form label {
  display: block;
  margin: 0.5rem 0 0.25rem;
  font-weight: bold;
}

.animal-form input,
.animal-form select {
  width: 100%;
  padding: 0.45rem;
  margin-bottom: 1rem;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.group-item {
  background-color: #fff;
  padding: 1rem;
  margin-bottom: 1rem;
  border-left: 4px solid #42b983;
  border-radius: 4px;
}

.remove-button {
  text-align: right;
}

.btn {
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-weight: 600;
  cursor: pointer;
  border: none;
}

.btn-subgroup {
  background-color: #42b983;
  color: white;
}

.btn-remove {
  background-color: #e74c3c;
  color: white;
}

.btn-submit {
  background-color: #2c3e50;
  color: white;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-actions {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  margin-top: 1.5rem;
}


/* Optional: remove bottom margin from parent group container */
.parent-group-dropdown, .child-group-dropdown {
  margin-bottom: 1.5rem;
}

.name-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.name-row .form-field {
  flex: 1;
}

textarea {
  width: 100%;
  padding: 0.45rem;
  border: 1px solid #ccc;
  border-radius: 4px;
  resize: vertical;
  margin-bottom: 1rem;
}


</style>

