<script setup>
import find from "lodash-es/find"
import { computed, ref, watch, nextTick } from "vue"

const skipUnwrap = { el: ref([]) };
let el = computed(() => skipUnwrap.el.value);

const props = defineProps({
    searchFields: {
        type: Object,
        required: true,
    },

    forcedVisibleSearchFields: {
        type: Array,
        required: true,
    },

    onChange: {
        type: Function,
        required: true,
    },

    onRemove: {
        type: Function,
        required: true,
    },
});

function visibility(searchField) {
    if (searchField.key === 'global') {
        return false;
    }
    if (searchField.value) {
        return true;
    }
    return props.forcedVisibleSearchFields.includes(searchField.key);
}

watch(props.forcedVisibleSearchFields, (inputs) => {
    const latestInput = inputs.length > 0 ? inputs[inputs.length -1] : null;

    if(!latestInput) {
        return;
    }

    nextTick().then(() => {
        const inputElement = find(el.value, (el) => {
            return el.__vnode.key ===  latestInput
        })

        if(inputElement) {
            inputElement.focus();
        }
    })
}, {immediate: true})
</script>

<template>
    <template v-for="(searchField, key) in searchFields">
        <div
            v-if="visibility(searchField)"
            :key="key"
            class="px-4 sm:px-0"
        >
            <div class="flex rounded-md shadow-sm relative mt-3">
                <label
                    :for="searchField.key"
                    class="inline-flex items-center px-4 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 mr-2 text-gray-400"

                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <span>{{ searchField.label }}</span></label>
                <input
                    :id="searchField.key"
                    :ref="skipUnwrap.el"
                    :key="searchField.key"
                    :name="searchField.key"
                    :value="searchField.value"
                    type="text"
                    class="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md focus:ring-indigo-500 focus:border-indigo-500 text-sm border-gray-300"
                    @input="onChange(searchField.key, $event.target.value)"
                >
                <div
                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                >
                    <button
                        class="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        :dusk="`remove-search-row-${searchField.key}`"
                        @click.prevent="onRemove(searchField.key)"
                    >
                        <span class="sr-only">Remove search</span>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </template>
</template>
