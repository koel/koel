<template>
  <BasicListSorter :items :field :order @sort="sort" />
</template>

<script setup lang="ts">
import BasicListSorter from '@/components/ui/BasicListSorter.vue'

withDefaults(defineProps<{
  field?: PodcastListSortField
  order?: SortOrder
}>(), {
  field: 'last_played_at',
  order: 'asc',
})

const emit = defineEmits<{ (e: 'sort', field: PodcastListSortField, order: SortOrder): void }>()

const items: { label: string, field: PodcastListSortField }[] = [
  { label: 'Last Played', field: 'last_played_at' },
  { label: 'Subscribed', field: 'subscribed_at' },
  { label: 'Title', field: 'title' },
  { label: 'Author', field: 'author' },
]

const sort = (field: PodcastListSortField, order: SortOrder) => emit('sort', field, order)
</script>
