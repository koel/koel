<template>
  <BasicListSorter :items :field :order @sort="sort" />
</template>

<script setup lang="ts">
import BasicListSorter from '@/components/ui/BasicListSorter.vue'

withDefaults(defineProps<{
  field?: ArtistListSortField
  order?: SortOrder
}>(), {
  field: 'name',
  order: 'asc',
})

const emit = defineEmits<{ (e: 'sort', field: ArtistListSortField, order: SortOrder): void }>()

const items: { label: string, field: ArtistListSortField }[] = [
  { label: 'Name', field: 'name' },
  { label: 'Date Added', field: 'created_at' },
]

const sort = (field: ArtistListSortField, order: SortOrder) => emit('sort', field, order)
</script>
