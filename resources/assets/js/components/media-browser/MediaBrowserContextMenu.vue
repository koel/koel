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
import { usePlayCollection } from '@/composables/usePlayCollection'

const props = defineProps<{ items: Array<Folder | Song> }>()
const { items } = toRefs(props)

const { MenuItem, trigger } = useContextMenu()
const { go, url } = useRouter()
const { toastWarning, toastSuccess } = useMessageToaster()
const { playCollection } = usePlayCollection()

const isForSingleFolder = computed(() => items.value?.length === 1 && items.value[0].type === 'folders')

let references: MediaReference[]

const openFolder = () => trigger(async () => go(url('media-browser', { folder: (items.value[0] as Folder).id })))

// folder shuffling has already been done server-side, but local songs shuffling is still needed.
const playSongs = (shuffle: boolean) =>
  trigger(() =>
    playCollection(() => playableStore.resolveSongsFromMediaReferences(references, shuffle), {
      shuffle,
      emptyMessage: 'Nothing to play.',
    }),
  )

const play = () => playSongs(false)
const shuffle = () => playSongs(true)

const queue = () =>
  trigger(async () => {
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
