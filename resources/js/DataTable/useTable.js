import qs from "qs";
import {computed, ref, onMounted, onUnmounted} from "vue";
import {clone, pick, findKey, filter, forEach} from "lodash";
import {usePage} from "@inertiajs/inertia-vue3";

export function useTable (name = 'default', debounce = 3000) {
    const dataProps = ['columns', 'sort', 'page', 'filters', 'searchFields', 'perPage'];
    const queryBuilderProps = computed(() => usePage().props.value[`${name}TableBuilderProps`]);
    const queryBuilderData = ref(pick(queryBuilderProps.value, dataProps));
    const paginationMeta = computed(() => pick(queryBuilderProps.value.resource, ['current_page', 'from', 'last_page', 'per_page', 'to', 'total']))
    const prefix = computed(() => name === 'default' ? '' : `${name}_`);
    const pageName = computed(() => queryBuilderProps.value.pageName);
    const isLoading = ref(false);


    const loadPage = page => queryBuilderData.value.page = page;
    const changePageLength = length => {
        queryBuilderData.value.page = 1;
        queryBuilderData.value.per_page = length;
    }


    const hasSearchFields = computed(() => {
        return Object.getOwnPropertyNames(queryBuilderProps.value.searchFields)
            .some(key => key !== 'global');
    })

    const toggleableColumns = computed(() => filter(queryBuilderProps.value.columns, column => column.can_be_hidden));
    const hasHiddenColumns = computed(() => toggleableColumns.value.some(column => column.hidden))

    const hasHeader = computed(() =>
        !!(toggleableColumns.value.length
        || queryBuilderProps.value.searchFields.global
        || queryBuilderProps.value.hasFilters
        || hasSearchFields.value)
    );

    const getColumnIndex = key => findKey(queryBuilderData.value.columns, column => column.key === key)

    function changeColumnStatus(key, visible) {
        queryBuilderData.value.columns[getColumnIndex(key)].hidden = !visible;
    }

    function sortBy(column) {
        if(queryBuilderData.value.sort === column) {
            queryBuilderData.value.sort = `-${column}`;
        } else {
            queryBuilderData.value.sort = column;
        }

        queryBuilderData.value.page = 1;
    }

    const show = (i) => {
        return !queryBuilderData.value.columns[i].hidden;
    }

    const header = (i) => {
        const columnData = clone(queryBuilderProps.value.columns[i]);
        columnData.onSort = sortBy;
        return columnData;
    }


//

    const forcedVisibleSearchFields = ref([]);

    function disableSearchField(key) {
        forcedVisibleSearchFields.value = forcedVisibleSearchFields.value.filter((search) => search !== key);

        changeSearchFieldValue(key, null, true);
    }

    function showSearchField(key) {
        forcedVisibleSearchFields.value.push(key);
    }

    const canBeReset = computed(() => {
        if(forcedVisibleSearchFields.value.length > 0){
            return true;
        }

        const queryStringData = qs.parse(location.search.substring(1));

        const page = queryStringData[pageName.value];

        if(page > 1) {
            return true;
        }

        let dirty = false

        forEach(["filter", "columns", "sort"], (key) => {
            const value = queryStringData[prefix.value + key];

            if(key === "sort" && value === queryBuilderProps.value.defaultSort) {
                return;
            }

            if(value !== undefined) {
                dirty = true
            }
        });

        return dirty;
    });

    function resetQuery() {
        forcedVisibleSearchFields.value = [];

        forEach(queryBuilderData.value.filters, (filter, key) => {
            queryBuilderData.value.filters[key].value = null;
        })

        forEach(queryBuilderData.value.searchFields, (filter, key) => {
            queryBuilderData.value.searchFields[key].value = null;
        })

        console.log(queryBuilderData)
        forEach(queryBuilderData.value.columns, (column, i) => {
            queryBuilderData.value.columns[i].hidden
                = column.can_be_hidden
                ? !queryBuilderProps.value.defaultVisibleToggleableColumns.includes(column.key)
                : false;
        })
        console.log(queryBuilderData)

        queryBuilderData.value.sort = null;
        queryBuilderData.value.page = 1;
    }

    const debounceTimeouts = {};
    const visitCancelToken = ref(null)

    function changeSearchFieldValue(key, value, imm = false) {
        clearTimeout(debounceTimeouts[key]);

        const changeEffect = () => {
            if(visitCancelToken.value){
                visitCancelToken.value.cancel();
            }

            queryBuilderData.value.searchFields[key].value = value;
            queryBuilderData.value.page = 1;
        };

        if (imm) {
            return changeEffect();
        }

        debounceTimeouts[key] = setTimeout(changeEffect, debounce);
    }

    function changeGlobalSearchValue(value) {
        changeSearchFieldValue("global", value);
    }

    function changeFilterValue(key, value) {
        queryBuilderData.value.filters[key].value = value;
        queryBuilderData.value.page = 1;
    }


    return {
        loadPage,
        getColumnIndex,
        paginationMeta,
        changePageLength,
        queryBuilderProps,
        queryBuilderData,
        hasSearchFields,
        toggleableColumns,
        hasHiddenColumns,
        prefix,
        pageName,
        isLoading,
        hasHeader,
        changeColumnStatus,
        sortBy,
        show,
        header,
        forcedVisibleSearchFields,
        disableSearchField,
        showSearchField,
        canBeReset,
        resetQuery,
        debounceTimeouts,
        visitCancelToken,
        changeSearchFieldValue,
        changeGlobalSearchValue,
        changeFilterValue,
    }
}
