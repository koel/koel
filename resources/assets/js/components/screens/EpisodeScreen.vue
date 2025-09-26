<template>
  <ScreenBase>
    <template #header>
      <ScreenHeaderSkeleton v-if="loading && !episode" />
      <ScreenHeader v-if="episode">
        <p class="text-base font-normal text-k-text-secondary">Episode</p>
        <h1 class="text-ellipsis overflow-hidden whitespace-nowrap" :title="episode.title">{{ episode.title }}</h1>

        <h2 class="text-2xl text-k-text-secondary">
          <a
            :href="url('podcasts.show', { id: episode.podcast_id })"
            class="!text-k-text-primary hover:!text-k-accent font-normal"
          >
            {{ episode.podcast_title }}
          </a>
        </h2>

        <template #thumbnail>
          <img :src="episode.episode_image" class="aspect-square object-cover" alt="Episode thumbnail">
        </template>

        <template #controls>
          <div class="flex gap-2">
            <Btn v-koel-tooltip="playing ? 'Pause' : 'Play'" highlight @click.prevent="playOrPause">
              <Icon v-if="playing" :icon="faPause" fixed-width />
              <Icon v-else :icon="faPlay" fixed-width />
            </Btn>

            <Btn
              v-if="episode.episode_link"
              v-koel-tooltip="'Visit episode webpage'" :href="episode.episode_link"
              gray
              tag="a"
              target="_blank"
            >
              <Icon :icon="faExternalLink" fixed-width />
            </Btn>

            <FavoriteButton
              v-if="episode.favorite"
              :favorite="episode.favorite"
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

    <div v-if="episode">
      <h3 class="text-3xl font-semibold mb-4">Description</h3>
      <div v-koel-new-tab class="description text-k-text-secondary" v-html="formattedDescription" />
    </div>
  </ScreenBase>
</template>

<script setup lang="ts">
import { faEllipsis, faExternalLink, faPause, faPlay } from '@fortawesome/free-solid-svg-icons'
import DOMPurify from 'dompurify'
import { orderBy } from 'lodash'
import { computed, ref, watch } from 'vue'
import { playableStore as episodeStore } from '@/stores/playableStore'
import { queueStore } from '@/stores/queueStore'
import { podcastStore } from '@/stores/podcastStore'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { defineAsyncComponent } from '@/utils/helpers'
import { playback } from '@/services/playbackManager'
import { useRouter } from '@/composables/useRouter'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useContextMenu } from '@/composables/useContextMenu'

import ScreenBase from '@/components/screens/ScreenBase.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import Btn from '@/components/ui/form/Btn.vue'
import ScreenHeaderSkeleton from '@/components/ui/ScreenHeaderSkeleton.vue'

const FavoriteButton = defineAsyncComponent(() => import('@/components/ui/FavoriteButton.vue'))
const ContextMenu = defineAsyncComponent(() => import('@/components/playable/PlayableContextMenu.vue'))

const { onScreenActivated, getRouteParam, triggerNotFound, url } = useRouter()
const { openContextMenu } = useContextMenu()

const loading = ref(false)
const episodeId = ref<Episode['id']>()
const episode = ref<Episode>()

const playing = computed(() => {
  return queueStore.current?.playback_state === 'Playing' && queueStore.current?.id === episodeId.value
})

const formattedDescription = computed(() => {
  return DOMPurify.sanitize(episode?.value?.episode_description ?? '')
})

const fetchDetails = async () => {
  episode.value = await episodeStore.resolve(episodeId.value!) as Episode
}

const playOrPause = async () => {
  queueStore.queueIfNotQueued(episode.value!)

  if (playing.value) {
    playback().pause()
    return
  }

  if (queueStore.current?.id === episodeId.value) {
    await playback().resume()
    return
  }

  // If the episode is not currently playing and the user clicks Play,
  // we want to play the episode from where they left off.
  // For that, we query the podcast to get the progress of the episode.
  const podcast = await podcastStore.resolve(episode.value!.podcast_id)

  let startingPoint = Math.min(episode.value!.length, podcast.state.progresses[episode.value!.id] || 0)

  if (startingPoint >= episode.value!.length) {
    startingPoint = 0
  }

  if (preferences.continuous_playback) {
    queueStore.replaceQueueWith(orderBy(await episodeStore.fetchEpisodesInPodcast(episode.value!.podcast_id), 'created_at'))
  }

  await playback().play(episode.value!, startingPoint)
}

watch(episodeId, async id => {
  if (!id || loading.value) {
    return
  }

  loading.value = true

  try {
    await fetchDetails()
  } catch (error: unknown) {
    useErrorHandler().handleHttpError(error, {
      404: () => triggerNotFound(),
    })
  } finally {
    loading.value = false
  }
})

const requestContextMenu = (event: MouseEvent) => openContextMenu<'PLAYABLES'>(ContextMenu, event, {
  playables: [episode.value!],
})

const toggleFavorite = () => episodeStore.toggleFavorite(episode.value!)

onScreenActivated('Episode', () => (episodeId.value = getRouteParam('id')!))
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
