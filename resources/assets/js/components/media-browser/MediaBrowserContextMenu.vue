<template>
  <ContextMenu ref="base">
    <li v-if="isForSingleFolder" @click="openFolder">Open</li>
    <li @click="play">Play</li>
    <li @click="shuffle">Shuffle</li>
    <li @click="addToQueue">Add to Queue</li>
  </ContextMenu>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { useRouter } from '@/composables/useRouter'
import { useContextMenu } from '@/composables/useContextMenu'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { pluralize } from '@/utils/formatters'
import { mediaBrowser } from '@/services/mediaBrowser'
import { playback } from '@/services/playbackManager'

const { base, ContextMenu, open, trigger } = useContextMenu()
const { go, url } = useRouter()
const { toastWarning, toastSuccess } = useMessageToaster()

const items = ref<Array<Folder | Song>>()

const isForSingleFolder = computed(() => items.value?.length === 1 && items.value[0].type === 'folders')

let references: MediaReference[]

const openFolder = () => trigger(async () => go(url('media-browser', { path: (items.value![0] as Folder).path })))

const play = () => trigger(async () => {
  const songs = await playableStore.resolveSongsFromMediaReferences(references)

  if (songs.length) {
    playback().queueAndPlay(songs)
    go(url('queue'))
  } else {
    toastWarning('Nothing to play.')
  }
})

const shuffle = () => trigger(async () => {
  const songs = await playableStore.resolveSongsFromMediaReferences(references, true)

  if (songs.length) {
    // folder shuffling has already been done server-side, but local songs shuffling is still needed.
    playback().queueAndPlay(songs, true)
    go(url('queue'))
  } else {
    toastWarning('Nothing to play.')
  }
})

const addToQueue = () => trigger(async () => {
  const songs = await playableStore.resolveSongsFromMediaReferences(references)

  if (songs.length) {
    queueStore.queueAfterCurrent(songs)
    toastSuccess(`${pluralize(songs, 'song')} added to queue.`)
  } else {
    toastWarning('Nothing to queue.')
  }
})

eventBus.on('MEDIA_BROWSER_CONTEXT_MENU_REQUESTED', async ({ pageX, pageY }, _items) => {
  items.value = _items
  references = mediaBrowser.extractMediaReferences(_items)
  await open(pageY, pageX)
})
</script>
