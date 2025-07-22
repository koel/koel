<template>
  <ContextMenu ref="base" data-testid="album-context-menu" extra-class="podcast-menu">
    <template v-if="podcast">
      <li @click="play">Play All</li>
      <li @click="shuffle">Shuffle All</li>
      <li class="separator" />
      <li @click="toggleFavorite">{{ podcast.favorite ? 'Undo Favorite' : 'Favorite' }}</li>
      <li class="separator" />
      <li @click="goToPodcast">Go to Podcast</li>
    </template>
  </ContextMenu>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { songStore } from '@/stores/songStore'
import { playbackService } from '@/services/playbackService'
import { useContextMenu } from '@/composables/useContextMenu'
import { useRouter } from '@/composables/useRouter'
import { eventBus } from '@/utils/eventBus'
import { podcastStore } from '@/stores/podcastStore'

const { go, url } = useRouter()
const { base, ContextMenu, open, trigger } = useContextMenu()

const podcast = ref<Podcast>()

const play = () => trigger(async () => {
  playbackService.queueAndPlay(await songStore.fetchForPodcast(podcast.value!))
  go(url('queue'))
})

const shuffle = () => trigger(async () => {
  playbackService.queueAndPlay(await songStore.fetchForPodcast(podcast.value!), true)
  go(url('queue'))
})

const toggleFavorite = () => trigger(() => podcastStore.toggleFavorite(podcast.value!))
const goToPodcast = () => trigger(() => go(url('podcasts.show', { id: podcast.value!.id })))

eventBus.on('PODCAST_CONTEXT_MENU_REQUESTED', async ({ pageX, pageY }, _podcast) => {
  podcast.value = _podcast
  await open(pageY, pageX)
})
</script>
