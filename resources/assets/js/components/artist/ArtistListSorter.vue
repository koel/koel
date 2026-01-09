<template>
  <BasicListSorter :items :field :order @sort="sort" />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import BasicListSorter from '@/components/ui/BasicListSorter.vue'

withDefaults(defineProps<{
  field?: ArtistListSortField
  order?: SortOrder
}>(), {
  field: 'name',
  order: 'asc',
})

const emit = defineEmits<{ (e: 'sort', field: ArtistListSortField, order: SortOrder): void }>()

const { t } = useI18n()

const items = computed<{ label: string, field: ArtistListSortField }[]>(() => [
  { label: t('artists.sortFields.name'), field: 'name' },
  { label: t('artists.sortFields.dateAdded'), field: 'created_at' },
])

const sort = (field: ArtistListSortField, order: SortOrder) => emit('sort', field, order)
</script>
