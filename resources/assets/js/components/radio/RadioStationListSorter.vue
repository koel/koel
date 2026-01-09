<template>
  <BasicListSorter :items :field :order @sort="sort" />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import BasicListSorter from '@/components/ui/BasicListSorter.vue'

const { t } = useI18n()

withDefaults(defineProps<{
  field?: RadioStationListSortField
  order?: SortOrder
}>(), {
  field: 'name',
  order: 'asc',
})

const emit = defineEmits<{ (e: 'sort', field: RadioStationListSortField, order: SortOrder): void }>()

const items = computed<{ label: string, field: RadioStationListSortField }[]>(() => [
  { label: t('radio.name'), field: 'name' },
  { label: t('songs.dateAdded'), field: 'created_at' },
])

const sort = (field: RadioStationListSortField, order: SortOrder) => emit('sort', field, order)
</script>
