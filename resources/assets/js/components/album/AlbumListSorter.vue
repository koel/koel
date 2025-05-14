<template>
  <BasicListSorter :items :field :order @sort="sort" />
</template>

<script setup lang="ts">
import BasicListSorter from '@/components/ui/BasicListSorter.vue'

withDefaults(defineProps<{
  field?: AlbumListSortField
  order?: SortOrder
}>(), {
  field: 'name',
  order: 'asc',
})

const emit = defineEmits<{ (e: 'sort', field: AlbumListSortField, order: SortOrder): void }>()

const items: { label: string, field: AlbumListSortField }[] = [
  { label: 'Name', field: 'name' },
  { label: 'Artist', field: 'artist_name' },
  { label: 'Release Year', field: 'year' },
  { label: 'Date Added', field: 'created_at' },
]

const sort = (field: AlbumListSortField, order: SortOrder) => emit('sort', field, order)
</script>
