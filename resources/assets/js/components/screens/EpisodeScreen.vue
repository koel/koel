<template>
  <ScreenBase @contextmenu.prevent="onContextMenu">
    <template #header>
      <ScreenHeaderSkeleton v-if="loading && !episode" />
      <ScreenHeader v-if="episode">
        <p class="text-base font-normal text-k-text-secondary">Episode</p>
        <h1 class="text-ellipsis overflow-hidden whitespace-nowrap" :title="episode.title">{{ episode.title }}</h1>

        <h2 class="text-2xl text-k-text-secondary">
          <a :href="`/#/podcasts/${episode.podcast_id}`" class="!text-k-text-primary hover:!text-k-accent font-normal">
            {{ episode.podcast_title }}
          </a>
        </h2>

        <template #thumbnail>
          <img :src="episode.episode_image" class="aspect-square object-cover" alt="Episode thumbnail" />
        </template>

        <template #controls>
          <div class="flex gap-2">
            <Btn highlight @click.prevent="playOrPause" v-koel-tooltip="playing ? 'Pause' : 'Play'">
              <Icon v-if="playing" :icon="faPause" fixed-width />
              <Icon v-else :icon="faPlay" fixed-width />
            </Btn>

            <Btn gray v-koel-tooltip="'Download'" @click.prevent="download">
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
      <div class="description text-k-text-secondary" v-html="formattedDescription" v-koel-new-tab />
    </div>
  </ScreenBase>
</template>

<script setup lang="ts">
import { faDownload, faExternalLink, faPause, faPlay } from '@fortawesome/free-solid-svg-icons'
import DOMPurify from 'dompurify'
import { computed, ref, watch } from 'vue'
import { podcastStore, preferenceStore as preferences, queueStore, songStore as episodeStore } from '@/stores'
import { eventBus } from '@/utils'
import { downloadService, playbackService } from '@/services'
import { useErrorHandler, useRouter } from '@/composables'

import ScreenBase from '@/components/screens/ScreenBase.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import Btn from '@/components/ui/form/Btn.vue'
import ScreenHeaderSkeleton from '@/components/ui/skeletons/ScreenHeaderSkeleton.vue'
import { orderBy } from 'lodash'

const { onScreenActivated, getRouteParam, triggerNotFound } = useRouter()

const loading = ref(false)
const episodeId = ref<string>()
const episode = ref<Episode>()

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

const playing = computed(() => {
  return queueStore.current?.playback_state === 'Playing' && queueStore.current?.id === episodeId.value
})

watch(episodeId, async id => {
  if (!id || loading.value) return

  loading.value = true

  try {
    await fetchDetails()
  } catch (error: unknown) {
    useErrorHandler().handleHttpError(error, {
      404: () => triggerNotFound()
    })
  } finally {
    loading.value = false
  }
})

const onContextMenu = (event: MouseEvent) => {
  if (!episode.value) return
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
