<template>
  <ul>
    <MenuItem v-if="isForSingleFolder" @click="openFolder">Open</MenuItem>
    <MenuItem @click="play">Play</MenuItem>
    <MenuItem @click="shuffle">Shuffle</MenuItem>
    <MenuItem @click="queue">Add to Queue</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { computed, onMounted, toRefs } from 'vue'
import { useRouter } from '@/composables/useRouter'
import { useContextMenu } from '@/composables/useContextMenu'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { pluralize } from '@/utils/formatters'
import { mediaBrowser } from '@/services/mediaBrowser'
import { playback } from '@/services/playbackManager'

const props = defineProps<{ items: Array<Folder | Song> }>()
const { items } = toRefs(props)

const { MenuItem, trigger } = useContextMenu()
const { go, url } = useRouter()
const { toastWarning, toastSuccess } = useMessageToaster()

const isForSingleFolder = computed(() => items.value?.length === 1 && items.value[0].type === 'folders')

let references: MediaReference[]

const openFolder = () => trigger(async () => go(url('media-browser', { path: (items.value[0] as Folder).path })))

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

const queue = () => trigger(async () => {
  const songs = await playableStore.resolveSongsFromMediaReferences(references)

  if (songs.length) {
    queueStore.queue(songs)
    toastSuccess(`${pluralize(songs, 'song')} added to queue.`)
  } else {
    toastWarning('Nothing to queue.')
  }
})

onMounted(() => {
  references = mediaBrowser.extractMediaReferences(items.value)
})
</script>
