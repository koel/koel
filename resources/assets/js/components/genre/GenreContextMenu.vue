<template>
  <ContextMenu ref="base" data-testid="genre-context-menu" extra-class="genre-menu">
    <template v-if="genre">
      <li @click="play">Play</li>
      <li @click="shuffle">Shuffle</li>
      <li @click="queue">Add to Queue</li>
    </template>
  </ContextMenu>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { useContextMenu } from '@/composables/useContextMenu'
import { eventBus } from '@/utils/eventBus'
import { playback } from '@/services/playbackManager'
import { playableStore } from '@/stores/playableStore'
import { useRouter } from '@/composables/useRouter'
import { queueStore } from '@/stores/queueStore'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { pluralize } from '@/utils/formatters'

const { base, ContextMenu, open, trigger } = useContextMenu()
const { toastSuccess } = useMessageToaster()
const { go } = useRouter()

const genre = ref<Genre>()

const play = () => trigger(async () => {
  playback().queueAndPlay(await playableStore.fetchSongsByGenre(genre.value!))
  go('queue')
})

const shuffle = () => trigger(async () => {
  playback().queueAndPlay(await playableStore.fetchSongsByGenre(genre.value!, true))
  go('queue')
})

const queue = () => trigger(async () => {
  const songs = await playableStore.fetchSongsByGenre(genre.value!)
  queueStore.queue(songs)
  toastSuccess(`${pluralize(songs, 'song')} added to queue.`)
})

eventBus.on('GENRE_CONTEXT_MENU_REQUESTED', async ({ pageX, pageY }, _genre) => {
  genre.value = _genre
  await open(pageY, pageX)
})
</script>
