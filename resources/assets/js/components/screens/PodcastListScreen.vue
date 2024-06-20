<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">
        Podcasts

        <template #controls>
          <div class="flex gap-2" v-if="!loading">
            <PodcastListSorter :field="sortParams.field" :order="sortParams.order" @sort="sort" />
            <ListFilter @change="onFilterChanged" />
            <BtnGroup uppercase>
              <Btn @click.prevent="requestAddPodcastForm" highlight>
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
      <span class="secondary d-block">Add a podcast to get started.</span>
    </ScreenEmptyState>

    <div v-else v-koel-overflow-fade class="-m-6 p-6 overflow-auto space-y-3 min-h-full">
      <template v-if="loading">
        <PodcastItemSkeleton v-for="i in 5" :key="i" />
      </template>
      <template v-else>
        <PodcastItem v-for="podcast in podcasts" :podcast="podcast" :key="podcast.id" />
      </template>
    </div>
  </ScreenBase>
</template>

<script setup lang="ts">
import { faAdd, faPodcast } from '@fortawesome/free-solid-svg-icons'
import { orderBy } from 'lodash'
import { computed, reactive, ref } from 'vue'
import { eventBus } from '@/utils'
import { useErrorHandler, useFuzzySearch, useRouter } from '@/composables'
import { podcastStore } from '@/stores'

import Btn from '@/components/ui/form/Btn.vue'
import BtnGroup from '@/components/ui/form/BtnGroup.vue'
import ListFilter from '@/components/song/SongListFilter.vue'
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

const sortParams = reactive<{ field: PodcastListSortField, order: SortOrder }>({
  field: 'last_played_at',
  order: 'desc'
})

const noPodcasts = computed(() => !loading.value && podcasts.value.length === 0)

const init = async () => {
  if (loading.value) return

  loading.value = true

  try {
    fuzzy.setDocuments(await podcastStore.fetchAll())
  } catch (error: any) {
    useErrorHandler().handleHttpError(error)
  } finally {
    loading.value = false
  }
}

const podcasts = computed(() => orderBy(fuzzy.search(keywords.value), sortParams.field, sortParams.order))

const onFilterChanged = (q: string) => (keywords.value = q)

const requestAddPodcastForm = () => eventBus.emit('MODAL_SHOW_ADD_PODCAST_FORM')

const sort = (field: PodcastListSortField) => {
  sortParams.field = field
  sortParams.order = sortParams.order === 'asc' ? 'desc' : 'asc'
}

onScreenActivated('Podcasts', async () => {
  if (!initialized) {
    initialized = true
    await init()
  }
})
</script>
