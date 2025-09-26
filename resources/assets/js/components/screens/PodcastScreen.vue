<template>
  <ScreenBase>
    <template #header>
      <ScreenHeaderSkeleton v-if="loading && !podcast" />
      <ScreenHeader v-if="podcast" :layout="headerLayout">
        <span :title="podcast.title">{{ podcast.title }}</span>

        <template #thumbnail>
          <article class="relative aspect-square block rounded-md overflow-hidden" data-testid="podcast-thumbnail">
            <div class="pointer-events-none">
              <img :src="podcast.image" alt="Podcast thumbnail">
            </div>
          </article>
        </template>

        <template #meta>
          <div>
            <p class="text-2xl text-k-text-primary mb-1">{{ podcast.author }}</p>
            <div
              ref="descriptionEl"
              v-koel-new-tab
              :class="{ 'cursor-pointer': description.overflown }"
              :title="descriptionTooltip"
              class="leading-5 line-clamp-3"
              @click="maybeExpandDescription"
              v-html="description.content"
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
              <Btn v-if="episodes" v-koel-tooltip="'Refresh'" success @click.prevent="refresh">
                <Icon :icon="faRotateRight" fixed-width />
                <span class="sr-only">Refresh Podcast</span>
              </Btn>
            </BtnGroup>

            <ListFilter v-if="episodes?.length" />

            <FavoriteButton
              v-if="podcast.favorite"
              :favorite="podcast.favorite"
              class="px-3.5 py-2"
              @toggle="toggleFavorite"
            />

            <Btn gray @click="requestContextMenu">
              <Icon :icon="faEllipsis" fixed-width />
              <span class="sr-only">More Actions</span>
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
        v-slot="{ item }: { item: Episode }"
        :item-height="161.5"
        :items="displayedEpisodes"
        @scroll="onListScroll"
      >
        <EpisodeItem :key="item.id" :podcast="podcast" :episode="item" />
      </VirtualScroller>
    </div>
  </ScreenBase>
</template>

<script setup lang="ts">
import DOMPurify from 'dompurify'
import { orderBy } from 'lodash'
import { faEllipsis, faPause, faPlay, faRotateRight } from '@fortawesome/free-solid-svg-icons'
import { computed, nextTick, onMounted, provide, reactive, ref } from 'vue'
import { useRouter } from '@/composables/useRouter'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { playableStore as episodeStore } from '@/stores/playableStore'
import { podcastStore } from '@/stores/podcastStore'
import { queueStore } from '@/stores/queueStore'
import { isEpisode } from '@/utils/typeGuards'
import { useFuzzySearch } from '@/composables/useFuzzySearch'
import { playback } from '@/services/playbackManager'
import { FilterKeywordsKey } from '@/symbols'
import { eventBus } from '@/utils/eventBus'
import { useContextMenu } from '@/composables/useContextMenu'
import { defineAsyncComponent } from '@/utils/helpers'

import ScreenBase from '@/components/screens/ScreenBase.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenHeaderSkeleton from '@/components/ui/ScreenHeaderSkeleton.vue'
import EpisodeItem from '@/components/podcast/EpisodeItem.vue'
import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import Btn from '@/components/ui/form/Btn.vue'
import ListFilter from '@/components/ui/ListFilter.vue'
import BtnGroup from '@/components/ui/form/BtnGroup.vue'
import EpisodeItemSkeleton from '@/components/podcast/EpisodeItemSkeleton.vue'

const FavoriteButton = defineAsyncComponent(() => import('@/components/ui/FavoriteButton.vue'))
const ContextMenu = defineAsyncComponent(() => import('@/components/podcast/PodcastContextMenu.vue'))

const { getRouteParam, go, triggerNotFound, url } = useRouter()
const { handleHttpError } = useErrorHandler()

const description = reactive({
  overflown: false,
  expanded: false,
  content: '',
})

const descriptionEl = ref<HTMLDivElement>()

const headerLayout = ref<ScreenHeaderLayout>('expanded')
const loading = ref(false)
const podcast = ref<Podcast>()
const episodes = ref<Episode[]>([])
const keywords = ref('')

provide(FilterKeywordsKey, keywords)

const { search } = useFuzzySearch<Episode>(episodes, ['title', 'episode_description'])
const { openContextMenu } = useContextMenu()

const fetchDetails = async (id: Podcast['id']) => {
  [podcast.value, episodes.value] = await Promise.all([
    podcastStore.resolve(id),
    episodeStore.fetchEpisodesInPodcast(id),
  ])
}

const init = async () => {
  const id = getRouteParam('id')

  if (!id || loading.value) {
    return
  }

  loading.value = true

  try {
    await fetchDetails(id)
    description.content = DOMPurify.sanitize(podcast.value?.description || '')
    await nextTick()
    if (descriptionEl.value) {
      description.overflown = descriptionEl.value.scrollHeight > descriptionEl.value.clientHeight
    }
  } catch (error: unknown) {
    handleHttpError(error, {
      404: () => triggerNotFound(),
    })
  } finally {
    loading.value = false
  }
}

const maybeExpandDescription = () => {
  if (!description.overflown || !descriptionEl.value) {
    return
  }

  description.expanded = !description.expanded
  descriptionEl.value.classList.toggle('line-clamp-3')
}

const requestContextMenu = (event: MouseEvent) => openContextMenu<'PODCAST'>(ContextMenu, event, {
  podcast: podcast.value!,
})

const descriptionTooltip = computed(() => {
  if (!description.overflown) {
    return ''
  }

  return description.expanded ? 'Collapse' : 'Expand'
})

const displayedEpisodes = computed(() => {
  if (!episodes.value) {
    return []
  }

  if (!keywords.value) {
    return episodes.value
  }

  return search(keywords.value)
})

const inProgress = computed(() => Boolean(podcast.value?.state.current_episode))

const currentPlayingItemIsPartOfPodcast = computed(() => {
  const currentPlayable = queueStore.current
  return currentPlayable
    && isEpisode(currentPlayable)
    && currentPlayable.podcast_id === podcast.value?.id
})

const podcastPlaying = computed(() => {
  if (!currentPlayingItemIsPartOfPodcast.value) {
    return false
  }

  return queueStore.current!.playback_state === 'Playing'
})

const playButtonLabel = computed(() => {
  if (headerLayout.value === 'collapsed') {
    return ''
  }

  if (podcastPlaying.value) {
    return ''
  }

  return inProgress.value ? 'Continue' : 'Start Listening'
})

const playOrPause = async () => {
  if (podcastPlaying.value) {
    playback().pause()
    return
  }

  if (currentPlayingItemIsPartOfPodcast.value) {
    await playback().resume()
    return
  }

  if (inProgress.value) {
    const currentEpisode = episodes.value?.find(episode => episode.id === podcast.value?.state.current_episode)
    if (!currentEpisode) {
      return
    }

    await playback().play(currentEpisode, podcast.value?.state.progresses[currentEpisode.id] || 0)
    return
  }

  if (!episodes.value?.length) {
    return
  }

  queueStore.replaceQueueWith(orderBy(episodes.value, 'created_at'))
  await playback().playFirstInQueue()
}

const refresh = async () => {
  if (loading.value) {
    return
  }

  loading.value = true

  try {
    episodes.value = await episodeStore.fetchEpisodesInPodcast(podcast.value!.id, true)
  } catch (error: unknown) {
    handleHttpError(error)
  } finally {
    loading.value = false
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

const toggleFavorite = () => podcastStore.toggleFavorite(podcast.value!)

onMounted(() => init())

eventBus.on('PODCAST_UNSUBSCRIBED', ({ id }) => id === podcast.value?.id && go(url('podcasts.index')))
</script>

<style scoped lang="postcss">
:deep(.items-wrapper) {
  @apply divide-y divide-k-border;
}
</style>
