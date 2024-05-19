<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">
        Podcasts

        <template #controls>
          <div class="flex gap-2" v-if="!loading">
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
import Fuse from 'fuse.js'
import { faAdd, faPodcast } from '@fortawesome/free-solid-svg-icons'
import { orderBy } from 'lodash'
import { computed, ref } from 'vue'
import { eventBus } from '@/utils'
import { useErrorHandler, useRouter } from '@/composables'
import { podcastStore } from '@/stores'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import Btn from '@/components/ui/form/Btn.vue'
import BtnGroup from '@/components/ui/form/BtnGroup.vue'
import PodcastItem from '@/components/podcast/PodcastItem.vue'
import ListFilter from '@/components/song/SongListFilter.vue'
import PodcastItemSkeleton from '@/components/ui/skeletons/PodcastItemSkeleton.vue'

const { onScreenActivated } = useRouter()

let initialized = false
const loading = ref(false)
const keywords = ref('')

let fuse: Fuse<Podcast> | null = null

const noPodcasts = computed(() => !loading.value && podcasts.value.length === 0)

const init = async () => {
  if (loading.value) return

  loading.value = true

  try {
    fuse = new Fuse(await podcastStore.fetchAll(), {
      keys: ['title', 'description', 'author']
    })
  } catch (error: any) {
    useErrorHandler().handleHttpError(error)
  } finally {
    loading.value = false
  }
}

const podcasts = computed(() => {
  let list = podcastStore.state.podcasts

  if (keywords.value) {
    list = fuse?.search(keywords.value).map(result => result.item) || []
  }

  return orderBy(list, 'subscribed_at', 'desc')
})

const onFilterChanged = (q: string) => (keywords.value = q)

const requestAddPodcastForm = () => eventBus.emit('MODAL_SHOW_ADD_PODCAST_FORM')

onScreenActivated('Podcasts', async () => {
  if (!initialized) {
    initialized = true
    await init()
  }
})
</script>
