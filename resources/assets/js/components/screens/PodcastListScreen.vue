<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">
        Podcasts
        <template #controls>
          <div v-if="!loading" class="flex gap-2">
            <Btn
              v-koel-tooltip
              :title="preferences.podcasts_favorites_only ? 'Show all' : 'Show favorites only'"
              class="border border-white/10"
              small
              transparent
              @click.prevent="toggleFavoritesOnly"
            >
              <Icon
                :icon="preferences.podcasts_favorites_only ? faStar : faEmptyStar"
                :class="preferences.podcasts_favorites_only && 'text-k-highlight'"
                size="sm"
              />
            </Btn>

            <PodcastListSorter
              :field="preferences.podcasts_sort_field"
              :order="preferences.podcasts_sort_order"
              @sort="sort"
            />

            <ListFilter />
            <BtnGroup uppercase>
              <Btn highlight @click.prevent="requestAddPodcastForm">
                <Icon :icon="faAdd" fixed-width />
              </Btn>
            </BtnGroup>
          </div>
        </template>
      </ScreenHeader>
    </template>

    <ScreenEmptyState v-if="noPodcasts">
      <template #icon>
        <Icon :icon="faPodcast" />
      </template>
      No podcasts found.
      <span class="secondary block">Add a podcast to get started.</span>
    </ScreenEmptyState>

    <div v-else v-koel-overflow-fade class="-m-6 p-6 overflow-auto space-y-3 min-h-full">
      <template v-if="loading">
        <PodcastItemSkeleton v-for="i in 5" :key="i" />
      </template>
      <template v-else>
        <PodcastItem v-for="podcast in podcasts" :key="podcast.id" :podcast="podcast" />
      </template>
    </div>
  </ScreenBase>
</template>

<script setup lang="ts">
import { faAdd, faPodcast, faStar } from '@fortawesome/free-solid-svg-icons'
import { faStar as faEmptyStar } from '@fortawesome/free-regular-svg-icons'
import { orderBy } from 'lodash'
import { computed, nextTick, provide, ref } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { podcastStore } from '@/stores/podcastStore'
import { useRouter } from '@/composables/useRouter'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useFuzzySearch } from '@/composables/useFuzzySearch'
import { FilterKeywordsKey } from '@/symbols'
import { preferenceStore as preferences } from '@/stores/preferenceStore'

import Btn from '@/components/ui/form/Btn.vue'
import BtnGroup from '@/components/ui/form/BtnGroup.vue'
import ListFilter from '@/components/ui/ListFilter.vue'
import PodcastItem from '@/components/podcast/PodcastItem.vue'
import PodcastItemSkeleton from '@/components/ui/skeletons/PodcastItemSkeleton.vue'
import PodcastListSorter from '@/components/podcast/PodcastListSorter.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'

const { onScreenActivated } = useRouter()
const fuzzy = useFuzzySearch<Podcast>([], ['title', 'description', 'author'])

let initialized = false
const loading = ref(false)
const keywords = ref('')

provide(FilterKeywordsKey, keywords)

const podcasts = computed(() => orderBy(
  keywords.value ? fuzzy.search(keywords.value) : podcastStore.state.podcasts,
  preferences.podcasts_sort_field,
  preferences.podcasts_sort_order,
))

const noPodcasts = computed(() => !loading.value && podcasts.value.length === 0)

const fetchPodcasts = async () => {
  if (loading.value) {
    return
  }

  loading.value = true

  try {
    await podcastStore.fetchAll(preferences.podcasts_favorites_only)
    fuzzy.setDocuments(podcastStore.state.podcasts)
  } catch (error: any) {
    useErrorHandler().handleHttpError(error)
  } finally {
    loading.value = false
  }
}

const requestAddPodcastForm = () => eventBus.emit('MODAL_SHOW_ADD_PODCAST_FORM')

const sort = (field: PodcastListSortField, order: SortOrder) => {
  preferences.podcasts_sort_order = order
  preferences.podcasts_sort_field = field
}

const toggleFavoritesOnly = async () => {
  preferences.podcasts_favorites_only = !preferences.podcasts_favorites_only

  podcastStore.reset()
  await nextTick()
  await fetchPodcasts()
}

onScreenActivated('Podcasts', async () => {
  if (!initialized) {
    initialized = true
    await fetchPodcasts()
  }
})
</script>
