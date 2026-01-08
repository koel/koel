<template>
  <BasicListSorter :items :field :order @sort="sort" />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import BasicListSorter from '@/components/ui/BasicListSorter.vue'

withDefaults(defineProps<{
  field?: PodcastListSortField
  order?: SortOrder
}>(), {
  field: 'last_played_at',
  order: 'asc',
})

const emit = defineEmits<{ (e: 'sort', field: PodcastListSortField, order: SortOrder): void }>()

const { t } = useI18n()

const items = computed<{ label: string, field: PodcastListSortField }[]>(() => [
  { label: t('podcasts.sortFields.lastPlayed'), field: 'last_played_at' },
  { label: t('podcasts.sortFields.subscribed'), field: 'subscribed_at' },
  { label: t('podcasts.sortFields.title'), field: 'title' },
  { label: t('podcasts.sortFields.author'), field: 'author' },
])

const sort = (field: PodcastListSortField, order: SortOrder) => emit('sort', field, order)
</script>
