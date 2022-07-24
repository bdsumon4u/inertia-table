<script setup>
import ButtonWithDropdown from "./ButtonWithDropdown.vue";
import {computed, ref} from "vue"

const props = defineProps({
    searchFields: {
        type: Object,
        required: true,
    },

    onAdd: {
        type: Function,
        required: true,
    },
});

const dropdown = ref(null)

const disabled = computed(() => {
    return !Object.getOwnPropertyNames(props.searchFields).some(key => {
        return key !== 'global' && !props.searchFields[key].value;
    })
})

function enableSearch(key) {
    props.onAdd(key);
    dropdown.value.hide()
}
</script>

<template>
  <ButtonWithDropdown
    ref="dropdown"
    dusk="add-search-row-dropdown"
    :disabled="disabled"
    class="w-auto"
  >
    <template #button>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span>Filter</span>
    </template>

    <div
      role="menu"
      aria-orientation="horizontal"
      aria-labelledby="add-search-input-menu"
      class="min-w-max"
    >
        <template v-for="(searchInput, key) in searchFields">
            <button
                :key="key"
                v-if="key !== 'global'"
                :dusk="`add-search-row-${searchInput.key}`"
                class="text-left w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                role="menuitem"
                @click.prevent="enableSearch(searchInput.key)"
            >
                {{ searchInput.label }}
            </button>
        </template>
    </div>
  </ButtonWithDropdown>
</template>
