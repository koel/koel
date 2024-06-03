<template>
  <ScreenBase>
    <template #header>
      <ScreenHeaderSkeleton v-if="loading && !podcast" />
      <ScreenHeader v-if="podcast" :layout="headerLayout">
        <span :title="podcast.title">{{ podcast.title }}</span>

        <template #thumbnail>
          <article class="relative aspect-square block rounded-md overflow-hidden" data-testid="podcast-thumbnail">
            <div class="pointer-events-none">
              <img :src="podcast.image" alt="Podcast thumbnail" />
            </div>
          </article>
        </template>

        <template #meta>
          <div>
            <p class="text-2xl text-k-text-primary mb-1">{{ podcast.author }}</p>
            <div
              ref="descriptionEl"
              :class="{ 'cursor-pointer': description.overflown }"
              :title="descriptionTooltip"
              class="leading-5 line-clamp-3"
              @click="maybeExpandDescription"
              v-html="description.content"
              v-koel-new-tab
            />
          </div>
        </template>

        <template #controls>
          <div class="flex gap-2 flex-wrap">
            <Btn v-if="episodes?.length" highlight uppercase @click.prevent="playOrPause">
              <Icon :icon="podcastPlaying ? faPause : faPlay" fixed-width />
              {{ playButtonLabel }}
            </Btn>
            <BtnGroup uppercase>
              <Btn v-if="episodes" success @click.prevent="refresh" v-koel-tooltip="'Refresh'">
                <Icon :icon="faRotateRight" fixed-width />
              </Btn>
              <Btn danger uppercase @click.prevent="unsubscribe" v-koel-tooltip="'Unsubscribe'">
                <Icon :icon="faTimes" fixed-width />
              </Btn>
            </BtnGroup>

            <ListFilter v-if="episodes?.length" @change="onFilterChanged" />

            <Btn tag="a" gray :href="podcast.link" target="_blank" v-koel-tooltip="'Visit podcast website'">
              <Icon :icon="faExternalLink" fixed-width />
            </Btn>
          </div>
        </template>
      </ScreenHeader>
    </template>

    <div class="-m-6 min-h-full flex flex-col flex-1 overflow-auto divide-y divide-white/10">
      <template v-if="loading && !episodes && !podcast">
        <EpisodeItemSkeleton v-for="i in 5" :key="i" />
      </template>
      <VirtualScroller
        v-if="episodes && podcast"
        :item-height="161.5"
        :items="displayedEpisodes"
        v-slot="{ item }: { item: Episode }"
        @scroll="onListScroll"
      >
        <EpisodeItem :podcast="podcast" :episode="item" :key="item.id" />
      </VirtualScroller>
    </div>
  </ScreenBase>
</template>

<script setup lang="ts">
import Fuse from 'fuse.js'
import DOMPurify from 'dompurify'
import { orderBy } from 'lodash'
import { faExternalLink, faPause, faPlay, faRotateRight, faTimes } from '@fortawesome/free-solid-svg-icons'
import { computed, nextTick, reactive, ref, watch } from 'vue'
import { useDialogBox, useErrorHandler, useRouter } from '@/composables'
import { podcastStore, queueStore, songStore as episodeStore } from '@/stores'
import { playbackService } from '@/services'
import { isEpisode } from '@/utils'

import ScreenBase from '@/components/screens/ScreenBase.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenHeaderSkeleton from '@/components/ui/skeletons/ScreenHeaderSkeleton.vue'
import EpisodeItem from '@/components/podcast/EpisodeItem.vue'
import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import Btn from '@/components/ui/form/Btn.vue'
import ListFilter from '@/components/song/SongListFilter.vue'
import BtnGroup from '@/components/ui/form/BtnGroup.vue'
import EpisodeItemSkeleton from '@/components/ui/skeletons/EpisodeItemSkeleton.vue'

const { showConfirmDialog } = useDialogBox()
const { onScreenActivated, getRouteParam, go, triggerNotFound } = useRouter()

const description = reactive({
  overflown: false,
  expanded: false,
  content: '',
})

const descriptionEl = ref<HTMLDivElement>()

const headerLayout = ref<ScreenHeaderLayout>('expanded')
const loading = ref(false)
const podcastId = ref<string>()
const podcast = ref<Podcast>()
const episodes = ref<Episode[]>()
const keywords = ref('')

let fuse: Fuse<Episode> | null = null

const fetchDetails = async () => {
  [podcast.value, episodes.value] = await Promise.all([
    podcastStore.resolve(podcastId.value!),
    episodeStore.fetchForPodcast(podcastId.value!)
  ])
}

watch(podcastId, async id => {
  if (!id || loading.value) return

  loading.value = true

  try {
    await fetchDetails()
    fuse = new Fuse(episodes.value!, {
      keys: ['title', 'episode_description']
    })

    description.content = DOMPurify.sanitize(podcast.value?.description || '')
    await nextTick()
    description.overflown = descriptionEl.value!.scrollHeight > descriptionEl.value!.clientHeight
  } catch (error: unknown) {
    useErrorHandler().handleHttpError(error, {
      404: () => triggerNotFound()
    })
  } finally {
    loading.value = false
  }
})

const maybeExpandDescription = () => {
  if (!description.overflown) return
  description.expanded = !description.expanded
  descriptionEl.value!.classList.toggle('line-clamp-3')
}

const descriptionTooltip = computed(() => {
  if (!description.overflown) return ''
  return description.expanded ? 'Collapse' : 'Expand'
})

const displayedEpisodes = computed(() => {
  if (!episodes.value) return []
  if (!keywords.value) return episodes.value

  return fuse?.search(keywords.value)?.map(result => result.item) || []
})

const inProgress = computed(() => Boolean(podcast.value?.state.current_episode))

const currentPlayingItemIsPartOfPodcast = computed(() => {
  const currentPlayable = queueStore.current
  return currentPlayable
    && isEpisode(currentPlayable)
    && currentPlayable.podcast_id === podcastId.value
})

const podcastPlaying = computed(() => {
  if (!currentPlayingItemIsPartOfPodcast.value) return false
  return queueStore.current!.playback_state === 'Playing'
})

const playButtonLabel = computed(() => {
  if (headerLayout.value === 'collapsed') return ''
  if (podcastPlaying.value) return ''
  return inProgress.value ? 'Continue' : 'Start Listening'
})

const playOrPause = () => {
  if (podcastPlaying.value) {
    playbackService.pause()
    return
  }

  if (currentPlayingItemIsPartOfPodcast.value) {
    playbackService.resume()
    return
  }

  if (inProgress.value) {
    const currentEpisode = episodes.value?.find(episode => episode.id === podcast.value?.state.current_episode)
    if (!currentEpisode) return

    playbackService.play(currentEpisode, podcast.value?.state.progresses[currentEpisode.id] || 0)
    return
  }

  if (!episodes.value?.length) return
  queueStore.replaceQueueWith(orderBy(episodes.value, 'created_at'))
  playbackService.playFirstInQueue()
}

const refresh = async () => {
  if (loading.value) return
  loading.value = true

  try {
    episodes.value = await episodeStore.fetchForPodcast(podcastId.value!, true)
  } catch (error: unknown) {
    useErrorHandler().handleHttpError(error)
  } finally {
    loading.value = false
  }
}

const unsubscribe = async () => {
  if (await showConfirmDialog(`Unsubscribe from ${podcast.value?.title}?`)) {
    await podcastStore.unsubscribe(podcast.value!)
    go(-1)
  }
}

let lastScrollTop = 0

const onListScroll = (e: Event) => {
  const scroller = e.target as HTMLElement

  if (scroller.scrollTop > 512 && lastScrollTop < 512) {
    headerLayout.value = 'collapsed'
  } else if (scroller.scrollTop < 512 && lastScrollTop > 512) {
    headerLayout.value = 'expanded'
  }

  lastScrollTop = scroller.scrollTop
}

const onFilterChanged = (q: string) => (keywords.value = q)

onScreenActivated('Podcast', () => (podcastId.value = getRouteParam('id')!))
</script>

<style scoped lang="postcss">
:deep(.items-wrapper) {
  @apply divide-y divide-k-border;
}
</style>
