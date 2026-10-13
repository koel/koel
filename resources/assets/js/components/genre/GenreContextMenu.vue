<template>
  <ul>
    <MenuItem @click="play">Play</MenuItem>
    <MenuItem @click="shuffle">Shuffle</MenuItem>
    <MenuItem @click="queue">Add to Queue</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'
import { useContextMenu } from '@/composables/useContextMenu'
import { playableStore } from '@/stores/playableStore'
import { queueStore } from '@/stores/queueStore'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { usePlayCollection } from '@/composables/usePlayCollection'
import { pluralize } from '@/utils/formatters'

const props = defineProps<{ genre: Genre }>()
const { genre } = toRefs(props)

const { MenuItem, trigger } = useContextMenu()
const { toastSuccess } = useMessageToaster()
const { playCollection } = usePlayCollection()

const playSongs = (random: boolean) =>
  trigger(() =>
    playCollection(() => playableStore.fetchSongsByGenre(genre.value, random), {
      emptyMessage: 'The genre is empty.',
    }),
  )

const play = () => playSongs(false)
const shuffle = () => playSongs(true)

const queue = () =>
  trigger(async () => {
    const songs = await playableStore.fetchSongsByGenre(genre.value)
    queueStore.queue(songs)
    toastSuccess(`${pluralize(songs, 'song')} added to queue.`)
  })
</script>
