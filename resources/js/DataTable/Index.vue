<script setup>
import qs from "qs";
import {computed, ref, watch} from "vue";
import {filter, forEach, map, pickBy, isEqual} from "lodash";
import Wrapper from "./Wrapper.vue";
import HeaderCell from "../InertiaTable/HeaderCell.vue";
import {useTable} from "./useTable";
import Filter from "./Filter.vue";
import GlobalSearch from "./GlobalSearch.vue";
import ResetQuery from "./ResetQuery.vue";
import AddSearchField from "./AddSearchField.vue";
import SearchFields from "./SearchFields.vue";
import ColumnToggle from "./ColumnToggle.vue";
import {Inertia} from "@inertiajs/inertia";
import Pagination from "./Pagination.vue";
import PageLength from "./PageLength.vue";

const props = defineProps({
    name: {
        type: String,
        default: 'default',
    }
})

const {
    loadPage,
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
} = useTable(props.name);


function getFilterForQuery() {
    let filtersWithValue = {};

    forEach(queryBuilderData.value.searchFields, (searchField) => {
        if (searchField.value !== null) {
            filtersWithValue[searchField.key] = searchField.value;
        }
    });

    forEach(queryBuilderData.value.filters, (filters) => {
        if (filters.value !== null) {
            filtersWithValue[filters.key] = filters.value;
        }
    });

    return filtersWithValue;
}

function getColumnsForQuery() {
    let visibleColumns = filter(queryBuilderData.value.columns, (column) => {
        return !column.can_be_hidden || column.can_be_hidden && !column.hidden;
    }).map(column => column.key).sort();

    console.log(visibleColumns, queryBuilderProps.value.defaultVisibleToggleableColumns)
    if (isEqual(visibleColumns, queryBuilderProps.value.defaultVisibleToggleableColumns)){
        return {};
    }

    return visibleColumns;
}

function dataForNewQueryString() {
    const filterForQuery = getFilterForQuery()
    const columnsForQuery = getColumnsForQuery()

    const queryData = {};

    if(Object.keys(filterForQuery).length > 0) {
        queryData.filter = filterForQuery;
    }

    if(Object.keys(columnsForQuery).length > 0) {
        queryData.columns = columnsForQuery;
    }

    const page = queryBuilderData.value.page;
    const sort = queryBuilderData.value.sort;
    const perPage = queryBuilderData.value.per_page;

    if(page > 1) {
        queryData.page = page;
    }

    if(perPage > 1) {
        queryData.per_page = perPage;
    }


    if(sort) {
        queryData.sort = sort;
    }

    return queryData;
}

function generateNewQueryString() {
    const queryStringData = qs.parse(location.search.substring(1));

    forEach(["filter", "columns", "sort"], (key) => {
        delete queryStringData[prefix.value + key]
    });

    delete queryStringData[pageName.value]

    forEach(dataForNewQueryString(), (value, key) => queryStringData[prefix.value + key] = value)

    let query = qs.stringify(queryStringData, {
        filter(prefix, value) {
            if (typeof value === "object" && value !== null) {
                return pickBy(value);
            }

            return value;
        },

        skipNulls: true,
        strictNullHandling: true,
    });

    if (!query || query === (pageName.value + "=1")) {
        query = "";
    }

    return query;
}

watch(queryBuilderData, () => {
    console.log(generateNewQueryString())
    Inertia.get(location.pathname + "?" +  generateNewQueryString(), {}, {
        replace: true,
        preserveState: true,
        preserveScroll: props.preserveScroll !== false,
        onBefore(){
            isLoading.value = true
        },
        onCancelToken(cancelToken) {
            visitCancelToken.value = cancelToken
        },
        onFinish() {
            isLoading.value = false
        },
        onSuccess() {
            console.log('success')
        }
    });
}, {deep: true})

</script>

<template>
    <Transition>
        <fieldset
            ref="tableFieldset"
            :key="`table-${name}`"
            :dusk="`table-${name}`"
            class="min-w-0 border border-solid p-3"
            :class="{'opacity-75': isLoading}"
        >
            <legend class="px-2 py-1 border">Personalia:</legend>
            <div class="flex flex-row flex-wrap sm:flex-nowrap justify-start px-4 sm:px-0">


                <div
                    v-if="queryBuilderProps.searchFields.global"
                    class="flex flex-row flex-wrap w-full sm:w-auto sm:flex-grow  mb-2 sm:mb-0 sm:mr-4"
                >
                    <div class="flex flex-wrap">
                        <div class=" mr-2 sm:mr-4">
                            <slot
                                name="tableFilter"
                                :has-filters="queryBuilderProps.filters"
                                :isFiltered="queryBuilderProps.isFiltered"
                                :filters="queryBuilderProps.filters"
                                :on-filter-change="changeFilterValue"
                            >
                                <Filter
                                    v-if="queryBuilderProps.filters"
                                    :isFiltered="queryBuilderProps.isFiltered"
                                    :filters="queryBuilderProps.filters"
                                    :on-filter-change="changeFilterValue"
                                />
                            </slot>
                        </div>
                        <slot
                            name="tableGlobalSearch"
                            :has-global-search="queryBuilderProps.searchFields.global"
                            :label="queryBuilderProps.searchFields.global?.label"
                            :value="queryBuilderProps.searchFields.global?.value"
                            :on-change="changeGlobalSearchValue"
                        >
                            <GlobalSearch
                                class="flex-1"
                                v-if="queryBuilderProps.searchFields.global"
                                :label="queryBuilderProps.searchFields.global.label"
                                :value="queryBuilderProps.searchFields.global.value"
                                :on-change="changeGlobalSearchValue"
                            />
                        </slot>
                    </div>
                    <div class="flex flex-wrap flex-end flex-1">
                        <slot
                            name="add-search-field"
                            :has-search-fields="hasSearchFields"
                            :search-fields="queryBuilderProps.searchFields"
                            :on-add="showSearchField"
                        >
                            <AddSearchField
                                v-if="hasSearchFields"
                                class="order-3 sm:order-4 mr-2 sm:mr-4"
                                :search-fields="queryBuilderProps.searchFields"
                                :on-add="showSearchField"
                            />
                        </slot>

                        <slot
                            name="tableColumns"
                            :has-toggleable-columns="toggleableColumns.length"
                            :columns="toggleableColumns"
                            :has-hidden-columns="hasHiddenColumns"
                            :on-change="changeColumnStatus"
                        >
                            <ColumnToggle
                                v-if="toggleableColumns.length"
                                class="order-4 mr-4 sm:mr-0 sm:order-5"
                                :columns="toggleableColumns"
                                :has-hidden-columns="hasHiddenColumns"
                                :on-change="changeColumnStatus"
                            />
                        </slot>
                        <slot
                            name="tableReset"
                            can-be-reset="canBeReset"
                            :on-click="resetQuery"
                        >
                            <div
                                v-if="canBeReset"
                                class="order-5 sm:order-3 sm:mr-4 ml-auto"
                            >
                                <ResetQuery :on-click="resetQuery"/>
                            </div>
                        </slot>
                    </div>
                </div>
            </div>

            <slot
                name="tableSearchRows"
                :search-fields="queryBuilderProps.searchFields"
                :forced-visible-search-inputs="forcedVisibleSearchFields"
                :on-change="changeSearchFieldValue"
            >
                <SearchFields
                    :search-fields="queryBuilderProps.searchFields"
                    :forced-visible-search-fields="forcedVisibleSearchFields"
                    :on-change="changeSearchFieldValue"
                    :on-remove="disableSearchField"
                />
            </slot>
            <slot
                name="wrapper"
            >
                <Wrapper :class="{ 'mt-3': hasHeader }">
                    <slot name="table">
                        <table class="min-w-full divide-y divide-gray-200 bg-white">
                            <thead class="bg-gray-50">
                            <slot
                                name="head"
                                :show="show"
                                :sort-by="sortBy"
                                :header="header"
                            >
                                <tr class="font-medium text-xs uppercase text-left tracking-wider text-gray-500 py-3 px-6">
                                    <HeaderCell
                                        v-for="(column, i) in queryBuilderProps.columns"
                                        :key="`table-${name}-header-${column.key}`"
                                        :cell="header(i)"
                                    />
                                </tr>
                            </slot>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-200">
                            <slot
                                name="body"
                                :show="show"
                            >
                                <tr
                                    v-for="(item, key) in queryBuilderProps.resource.data"
                                    :key="`table-${name}-row-${key}`"
                                    class=""
                                >
                                    <td
                                        v-for="(column, i) in queryBuilderProps.columns"
                                        v-show="show(i)"
                                        :key="`table-${name}-row-${key}-column-${column.key}`"
                                        class="text-sm py-4 px-6 text-gray-500 whitespace-nowrap"
                                    >
                                        <slot
                                            :name="`cell(${column.key})`"
                                            :item="item"
                                        >
                                            {{ item[column.key] }}
                                        </slot>
                                    </td>
                                </tr>
                            </slot>
                            </tbody>
                        </table>
                    </slot>

                    <div
                        class="bg-white px-4 py-3 flex gap-x-1 items-center justify-between border-t border-gray-200 sm:px-6">
                        <slot
                            name="pageLength"
                            :pageLength="parseInt(queryBuilderProps.per_page)"
                            :changePageLength="changePageLength"
                        >
                            <div class="flex items-center text-sm">
                                <span class="mr-1">Show</span>
                                <PageLength :page-length="parseInt(queryBuilderProps.resource.per_page)" :on-change="changePageLength"/>
                                <span class="ml-1">items</span>
                            </div>
                        </slot>

                        <slot name="pagination">
                            <Pagination :pagination="queryBuilderProps.resource" :load-page="loadPage"/>
                        </slot>
                    </div>
                </Wrapper>
            </slot>
        </fieldset>
    </Transition>
</template>
