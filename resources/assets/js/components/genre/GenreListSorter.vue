<template>
  <BasicListSorter :field :items :order @sort="sort" />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import BasicListSorter from '@/components/ui/BasicListSorter.vue'

const { t } = useI18n()

withDefaults(defineProps<{
  field?: GenreListSortField
  order?: SortOrder
}>(), {
  field: 'name',
  order: 'asc',
})

const emit = defineEmits<{ (e: 'sort', field: GenreListSortField, order: SortOrder): void }>()

const items = computed<{ label: string, field: GenreListSortField }[]>(() => [
  { label: t('albums.name'), field: 'name' },
  { label: t('songs.songCount'), field: 'song_count' },
])

const sort = (field: GenreListSortField, order: SortOrder) => emit('sort', field, order)
</script>
