<template>
  <BasicListSorter :field :items :order @sort="sort" />
</template>

<script setup lang="ts">
import BasicListSorter from '@/components/ui/BasicListSorter.vue'

withDefaults(defineProps<{
  field?: GenreListSortField
  order?: SortOrder
}>(), {
  field: 'name',
  order: 'asc',
})

const emit = defineEmits<{ (e: 'sort', field: GenreListSortField, order: SortOrder): void }>()

const items: { label: string, field: GenreListSortField }[] = [
  { label: 'Name', field: 'name' },
  { label: 'Song Count', field: 'song_count' },
]

const sort = (field: GenreListSortField, order: SortOrder) => emit('sort', field, order)
</script>
