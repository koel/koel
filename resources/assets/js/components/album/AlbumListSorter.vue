<template>
  <BasicListSorter :items :field :order @sort="sort" />
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import BasicListSorter from '@/components/ui/BasicListSorter.vue'

withDefaults(defineProps<{
  field?: AlbumListSortField
  order?: SortOrder
}>(), {
  field: 'name',
  order: 'asc',
})

const emit = defineEmits<{ (e: 'sort', field: AlbumListSortField, order: SortOrder): void }>()

const { t } = useI18n()

const items: { label: string, field: AlbumListSortField }[] = [
  { label: t('albums.sortFields.name'), field: 'name' },
  { label: t('albums.sortFields.artist_name'), field: 'artist_name' },
  { label: t('albums.sortFields.year'), field: 'year' },
  { label: t('albums.sortFields.created_at'), field: 'created_at' },
]

const sort = (field: AlbumListSortField, order: SortOrder) => emit('sort', field, order)
</script>
