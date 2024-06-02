<template>
  <a
    data-testid="episode-item"
    class="group relative flex flex-col md:flex-row gap-4 px-6 py-5 !text-k-text-primary hover:bg-white/10 duration-200"
    :class="isCurrentEpisode && 'current'"
    :href="`/#/episodes/${episode.id}`"
    @contextmenu.prevent="requestContextMenu"
    @dragstart="onDragStart"
  >
    <Icon :icon="faBookmark" size="xl" class="absolute -top-1 right-3 text-k-accent" v-if="isCurrentEpisode" />
    <button
      class="hidden md:block md:flex-[0_0_128px] relative overflow-hidden rounded-lg active:scale-95"
      role="button"
      @click.prevent="playOrPause"
    >
      <img
        :src="episode.episode_image"
        alt="Episode thumbnail"
        class="w-[128px] aspect-square object-cover"
        loading="lazy"
      />
      <span class="absolute top-0 left-0 w-full h-full group-hover:bg-black/40 z-10" />
      <span
        class="absolute flex opacity-0 items-center justify-center w-[48px] aspect-square rounded-full top-1/2
        left-1/2 -translate-x-1/2 -translate-y-1/2 bg-k-highlight group-hover:opacity-100 duration-500 transition z-20"
      >
        <Icon v-if="isPlaying" :icon="faPause" class="text-white" size="2xl" />
        <Icon v-else :icon="faPlay" class="ml-1 text-white" size="2xl" />
      </span>
    </button>
    <div class="flex-1">
      <time
        :datetime="episode.created_at"
        :title="episode.created_at"
        class="block uppercase text-sm mb-1 text-k-text-secondary"
      >
        {{ publicationDateForHumans }}
      </time>

      <h3 class="text-xl" :title="episode.title">{{ episode.title }}</h3>
      <div class="description text-k-text-secondary mt-3 line-clamp-3" v-html="description" />
    </div>
    <div class="md:flex-[0_0_96px] text-sm text-k-text-secondary flex md:flex-col items-center justify-center">
      <span class="block md:mb-2">{{ timeLeft ? timeLeft : 'Played' }}</span>
      <div class="px-4 w-full">
        <EpisodeProgress v-if="shouldShowProgress" :episode="episode" :position="currentPosition" />
      </div>
    </div>
  </a>
</template>

<script setup lang="ts">
import DOMPurify from 'dompurify'
import { orderBy } from 'lodash'
import { faBookmark, faPause, faPlay } from '@fortawesome/free-solid-svg-icons'
import { computed, defineAsyncComponent, toRefs } from 'vue'
import { eventBus, secondsToHis } from '@/utils'
import { useDraggable } from '@/composables'
import { formatTimeAgo } from '@vueuse/core'
import { playbackService } from '@/services'
import { preferenceStore as preferences, queueStore, songStore as episodeStore } from '@/stores'

const EpisodeProgress = defineAsyncComponent(() => import('@/components/podcast/EpisodeProgress.vue'))

const props = defineProps<{ episode: Episode, podcast: Podcast }>()
const { episode, podcast } = toRefs(props)

const { startDragging } = useDraggable('playables')

const publicationDateForHumans = computed(() => {
  const publishedAt = new Date(episode.value.created_at)

  if ((Date.now() - publishedAt.getTime()) / (1000 * 60 * 60 * 24) < 31) {
    return formatTimeAgo(publishedAt)
  }

  return publishedAt.toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
})

const currentPosition = computed(() => podcast.value.state.progresses[episode.value.id] || 0)

const timeLeft = computed(() => {
  if (currentPosition.value === 0) return secondsToHis(episode.value.length)
  const secondsLeft = episode.value.length - currentPosition.value
  return secondsLeft === 0 ? 0 : secondsToHis(secondsLeft)
})

const shouldShowProgress = computed(() => timeLeft.value !== 0 && episode.value.length && currentPosition.value)
const isCurrentEpisode = computed(() => podcast.value.state.current_episode === episode.value.id)
const description = computed(() => DOMPurify.sanitize(episode.value.episode_description))

const onDragStart = (event: DragEvent) => startDragging(event, episode.value)
const requestContextMenu = (event: MouseEvent) => eventBus.emit('PLAYABLE_CONTEXT_MENU_REQUESTED', event, episode.value)

const isPlaying = computed(() => episode.value.playback_state === 'Playing')

const playOrPause = async () => {
  if (isPlaying.value) {
    return playbackService.pause()
  }

  if (episode.value.playback_state === 'Paused') {
    return playbackService.resume()
  }

  if (preferences.continuous_playback) {
    queueStore.replaceQueueWith(orderBy(await episodeStore.fetchForPodcast(podcast.value.id), 'created_at'))
  }

  playbackService.play(episode.value, currentPosition.value >= episode.value.length ? 0 : currentPosition.value)
}
</script>

<style scoped lang="postcss">
.description {
  :deep(p) {
    @apply mb-3;
  }

  :deep(a) {
    @apply text-k-text-primary hover:text-k-accent;
  }
}
</style>
