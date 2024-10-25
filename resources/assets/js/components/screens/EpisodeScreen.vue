<template>
  <ScreenBase @contextmenu.prevent="onContextMenu">
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

            <Btn v-koel-tooltip="'Download'" gray @click.prevent="download">
              <Icon :icon="faDownload" fixed-width />
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
import { faDownload, faExternalLink, faPause, faPlay } from '@fortawesome/free-solid-svg-icons'
import DOMPurify from 'dompurify'
import { orderBy } from 'lodash'
import { computed, ref, watch } from 'vue'
import { songStore as episodeStore } from '@/stores/songStore'
import { queueStore } from '@/stores/queueStore'
import { podcastStore } from '@/stores/podcastStore'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { eventBus } from '@/utils/eventBus'
import { downloadService } from '@/services/downloadService'
import { playbackService } from '@/services/playbackService'
import { useRouter } from '@/composables/useRouter'
import { useErrorHandler } from '@/composables/useErrorHandler'

import ScreenBase from '@/components/screens/ScreenBase.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import Btn from '@/components/ui/form/Btn.vue'
import ScreenHeaderSkeleton from '@/components/ui/skeletons/ScreenHeaderSkeleton.vue'

const { onScreenActivated, getRouteParam, triggerNotFound, url } = useRouter()

const loading = ref(false)
const episodeId = ref<string>()
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
    playbackService.pause()
    return
  }

  if (queueStore.current?.id === episodeId.value) {
    await playbackService.resume()
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
    queueStore.replaceQueueWith(orderBy(await episodeStore.fetchForPodcast(episode.value!.podcast_id), 'created_at'))
  }

  await playbackService.play(episode.value!, startingPoint)
}

const download = () => downloadService.fromPlayables(episode.value!)

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

const onContextMenu = (event: MouseEvent) => {
  if (!episode.value) {
    return
  }
  eventBus.emit('PLAYABLE_CONTEXT_MENU_REQUESTED', event, episode.value)
}

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
