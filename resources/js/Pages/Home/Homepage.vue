<template>
    <div class="my-4 mx-[100px]">
        <form @submit.prevent="submit" class="flex flex-col space-y-2">
            <div class="flex space-x-2 items-center" v-for="group_input, i in group_inputs" :key="i">
                <div class="flex flex-col space-y-2">
                    <label>Classification</label>
                    <select v-model="group_input.class_id" :disabled="i < group_inputs.length - 1">
                        <option :value="null" disabled>Select classification</option>
                        <option v-for="classification, j in classifications" :key="j" :value="classification.id" :label="classification.name" :disabled="group_input.disabled_options.class[j]"></option>
                    </select>
                </div>

                <div class="flex flex-col space-y-2">
                    <label>Level</label>
                    <select v-model="group_input.level_id" :disabled="i < group_inputs.length - 1">
                        <option :value="null" disabled>Select level</option>
                        <option v-for="level, j in levels" :key="j" :value="level.id" :label="level.name + (level.alt_name ? '/' + level.alt_name : '')" :disabled="group_input.disabled_options.level[j]"></option>
                    </select>
                </div>

                <div class="flex flex-col space-y-2">
                    <label>Name</label>
                    <input placeholder="Name of group" type="text" v-model="group_input.name"/>
                </div>
            </div>

            <button class="text-white w-32 py-2 px-4 rounded-md bg-green-500 cursor-pointer">Submit</button>
        </form>
        <div class="flex flex-row space-x-2 mt-2">
            <button :disabled="disableIncrement" class="text-white w-32 py-2 px-4 rounded-md" :class="disableIncrement ? 'bg-gray-300 cursor-not-allowed' : 'bg-blue-500 cursor-pointer'" @click="group_count++">Increment</button>
            <button :disabled="disableDecrement" class="text-white w-32 py-2 px-4 rounded-md" :class="disableDecrement ? 'bg-gray-300 cursor-not-allowed' : 'bg-blue-500 cursor-pointer'"  @click="group_count--">Decrement</button>
        </div>
        
    </div>
</template>
<script>
import { router } from '@inertiajs/vue3';
export default {
    // classifications and levels should already be in order when brought back
    props: ['classifications', 'levels', 'groups', 'animals'],
    data() {
        return {
            group_count: 1,
            group_inputs: [this.initialiseGroupInputs(true)]
        }
    },
    watch: {
        group_count(val, prev_val) {
            let diff = val - prev_val;
            for (let i=0; i < Math.abs(diff); i++) {
                diff > 0 ? this.group_inputs.push(this.initialiseGroupInputs()) : this.group_inputs.pop();
            }
            console.log(this.group_inputs);
        },
        current_class_level_index() {
            this.group_inputs[group_inputs_length - 1].disabled_options = this.getLastGroupInputDisableOptions();
        },

        // selectedClassifications() {
        //     this.calculateAndSetDisableOptions();
        // },
        // selectedLevels() {
        //     this.calculateAndSetDisableOptions();
        // }
    },
    computed: {
        selectedClassifications() {
            return this.group_inputs.map(i => i.class_id);
        },
        selectedLevels() {
            return this.group_inputs.map(i => i.level_id);
        },
        disableIncrement() {
            const group_inputs_length = this.group_inputs.length;
            if (group_inputs_length > 0) {
                const last_input_index = group_inputs_length - 1;
                return this.group_inputs[last_input_index].class_id == null || this.group_inputs[last_input_index].level_id == null || this.group_inputs[last_input_index].name == null ||  this.group_inputs[last_input_index].level_id == null || this.group_inputs[last_input_index].name === "";
            }
            return false;
        },
        disableDecrement() {
            return this.group_inputs.length <= 1;
        },

        previous_class_level_index() {
            var value = {last_class_index: -1, last_level_index: -1};
            const group_inputs_length = this.group_inputs.length;
            if (group_inputs_length > 1) {
                const group_input = this.group_inputs[group_inputs_length - 2];
                value.last_class_index = group_input.class_id ? this.findIndexOf(this.classifications, "id", group_input.class_id) : -1;
                value.last_level_index = group_input.level_id ? this.findIndexOf(this.levels, "id", group_input.level_id) : -1;
            }
            return value;
        },
        current_class_level_index() {
            var value = {last_class_index: -1, last_level_index: -1};
            const group_inputs_length = this.group_inputs.length;
            if (group_inputs_length > 0) {
                const group_input = this.group_inputs[group_inputs_length - 1];
                value.last_class_index = group_input.class_id ? this.findIndexOf(this.classifications, "id", group_input.class_id) : -1;
                value.last_level_index = group_input.level_id ? this.findIndexOf(this.levels, "id", group_input.level_id) : -1;
            }
            return value;
        },
    },
    methods: {
        submit() {
            console.log(this.group_inputs);
        },
        findIndexOf(arr, property, val) {
            for (let i=0; i < arr.length; i++) {
                if (arr[i][property] === val) {
                    return i;
                }
            }

            return -1;
        },
        initialiseGroupInputs(isFirst = false) {
            return {class_id: isFirst ? 1 : null, level_id: isFirst ? 5 : null, name: null, disabled_options: this.getLastGroupInputDisableOptions()};
        },
        // initialiseSetGroupInputs() {
        //     const disabled_options = this.generateDisabledOptions();
        //     return [
        //         {class_id: 1, level_id: 5, disabled_options: disabled_options},
        //         {class_id: 2, level_id: 5, disabled_options: disabled_options},
        //         {class_id: 3, level_id: 5, disabled_options: disabled_options},
        //         {class_id: 4, level_id: 5, disabled_options: disabled_options},
        //         {class_id: 5, level_id: 5, disabled_options: disabled_options},
        //         {class_id: 6, level_id: 5, disabled_options: disabled_options},
        //         {class_id: 7, level_id: 5, disabled_options: disabled_options},
        //         {class_id: 8, level_id: 5, disabled_options: disabled_options},
        //     ];
        // },
        // generateDisabledOptions() {
        //     return {class: this.classifications.map(c => false), level: this.levels.map(l => false)};
        // },
        getLastGroupInputDisableOptions() {
            console.log(this.group_inputs);
            const key = this.group_inputs.length - 1;
            const prev_key = key - 1;
            var disabled_options = {class: this.classifications.map(c => false), level: this.levels.map(l => false)};

            if (prev_key >= 0) {
                const levels_count = this.levels.length;

                const prev_group = this.group_inputs[prev_key];
                const pci = prev_group.class_id ? this.findIndexOf(this.classifications, "id", prev_group.class_id) : null;
                const pli = prev_group.level_id ? this.findIndexOf(this.levels, "id", prev_group.level_id) : null;

                const cur_group = this.group_inputs[key];
                const cci = cur_group.class_id ? this.findIndexOf(this.classifications, "id", cur_group.class_id) : null;
                const cli = cur_group.level_id ? this.findIndexOf(this.levels, "id", cur_group.level_id) : null;

                disabled_options.class = this.classifications.map(function(value, key) {
                    return !(key >= pci) || (key == pci && pli == levels_count - 1) || pci == key && cli !== null && cli <= pli;
                });
                
                disabled_options.level = this.levels.map(function(value, key) {
                    return pci == cci && key <= pli;
                });
            }

            return disabled_options;
        },
        calculateAndSetDisableOptions() {
            const levels_count = this.levels.length;

            for (let key=0; key < this.group_inputs.length; key++) {
                const prev_key = key - 1;

                if (prev_key >= 0) {
                    const prev_group = this.group_inputs[prev_key];
                    const pci = prev_group.class_id ? this.findIndexOf(this.classifications, "id", prev_group.class_id) : null;
                    const pli = prev_group.level_id ? this.findIndexOf(this.levels, "id", prev_group.level_id) : null;

                    const cur_group = this.group_inputs[key];
                    const cci = cur_group.class_id ? this.findIndexOf(this.classifications, "id", cur_group.class_id) : null;
                    const cli = cur_group.level_id ? this.findIndexOf(this.levels, "id", cur_group.level_id) : null;

                    this.group_inputs[key].disabled_options.class = this.classifications.map(function(value, key) {
                        return !(key >= pci) || (key == pci && pli == levels_count - 1) || pci == key && cli !== null && cli <= pli;
                    });
                    
                    this.group_inputs[key].disabled_options.level = this.levels.map(function(value, key) {
                        return pci == cci && key <= pli;
                    });
                }  
            }
        },
    },
    mounted() {
        console.log("hello");
    }
}

</script>
<style scoped></style>
