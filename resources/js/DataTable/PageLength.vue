<script setup>
import Dropdown from "@/Jetstream/Dropdown.vue";

const props = defineProps({
    options: {
        default: [10, 25, 50, 100],
    },
    pageLength: {
        type: Number,
        required: true,
    },

    onChange: {
        type: Function,
        required: true,
    },
})
</script>

<template>
    <Dropdown width="-1" direction="up" :content-classes="['p-1 bg-white rounded dark:bg-gray-700']" align="left">
        <template #trigger>
            <button
                type="button"
                :disabled="false"
                class="w-full bg-white border rounded-md shadow-sm px-4 py-2 inline-flex justify-center text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-indigo-500"
                :class="{'border-green-300': false, 'border-gray-300': true, 'cursor-not-allowed': false }"
                aria-haspopup="true"
                ref="button"
            >
                {{ pageLength }}
            </button>
        </template>

        <template #content>
            <ul class="text-sm text-center font-semibold text-gray-700 dark:text-gray-200" aria-labelledby="dropdownDefault">
                <li v-for="length in options">
                    <a @click.prevent="() => onChange(length)" href="#" class="block py-2 px-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">{{ length }}</a>
                </li>
            </ul>
        </template>
    </Dropdown>
</template>
