<template>
  <BasicListSorter :items :field :order @sort="sort" />
</template>

<script setup lang="ts">
import BasicListSorter from '@/components/ui/BasicListSorter.vue'

withDefaults(defineProps<{
  field?: RadioStationListSortField
  order?: SortOrder
}>(), {
  field: 'name',
  order: 'asc',
})

const emit = defineEmits<{ (e: 'sort', field: RadioStationListSortField, order: SortOrder): void }>()

const items: { label: string, field: RadioStationListSortField }[] = [
  { label: 'Name', field: 'name' },
  { label: 'Date Added', field: 'created_at' },
]

const sort = (field: RadioStationListSortField, order: SortOrder) => emit('sort', field, order)
</script>
