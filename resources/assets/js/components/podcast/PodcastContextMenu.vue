<template>
  <ContextMenu ref="base" data-testid="podcast-context-menu" extra-class="podcast-menu">
    <template v-if="podcast">
      <li @click="play">Play All</li>
      <li @click="shuffle">Shuffle All</li>
      <li class="separator" />
      <li @click="toggleFavorite">{{ podcast.favorite ? 'Undo Favorite' : 'Favorite' }}</li>
      <li class="separator" />
      <li @click="visitWebsite">Visit Website</li>
      <li class="separator" />
      <li @click="unsubscribe">Unsubscribe</li>
    </template>
  </ContextMenu>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { playableStore } from '@/stores/playableStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { useRouter } from '@/composables/useRouter'
import { eventBus } from '@/utils/eventBus'
import { podcastStore } from '@/stores/podcastStore'
import { playback } from '@/services/playbackManager'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'

const { go, url } = useRouter()
const { base, ContextMenu, open, trigger } = useContextMenu()
const { showConfirmDialog } = useDialogBox()
const { toastSuccess } = useMessageToaster()

const podcast = ref<Podcast>()

const play = () => trigger(async () => {
  playback().queueAndPlay(await playableStore.fetchEpisodesInPodcast(podcast.value!))
  go(url('queue'))
})

const shuffle = () => trigger(async () => {
  playback().queueAndPlay(await playableStore.fetchEpisodesInPodcast(podcast.value!), true)
  go(url('queue'))
})

const unsubscribe = async () => {
  if (await showConfirmDialog('Unsubscribe from podcast?')) {
    await podcastStore.unsubscribe(podcast.value!)
    toastSuccess('Podcast unsubscribed.')
    eventBus.emit('PODCAST_UNSUBSCRIBED', podcast.value!)
  }
}

const visitWebsite = () => trigger(() => window.open(podcast.value?.link))

const toggleFavorite = () => trigger(() => podcastStore.toggleFavorite(podcast.value!))

eventBus.on('PODCAST_CONTEXT_MENU_REQUESTED', async ({ pageX, pageY }, _podcast) => {
  podcast.value = _podcast
  await open(pageY, pageX)
})
</script>
