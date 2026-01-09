<template>
  <ul>
    <MenuItem @click="play">{{ t('ui.buttons.play') }}</MenuItem>
    <MenuItem @click="shuffle">{{ t('albums.shuffle') }}</MenuItem>
    <MenuItem @click="queue">{{ t('misc.addToQueue') }}</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'
import { useI18n } from 'vue-i18n'
import { useContextMenu } from '@/composables/useContextMenu'
import { playback } from '@/services/playbackManager'
import { playableStore } from '@/stores/playableStore'
import { useRouter } from '@/composables/useRouter'
import { queueStore } from '@/stores/queueStore'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { pluralize } from '@/utils/formatters'

const props = defineProps<{ genre: Genre }>()
const { genre } = toRefs(props)

const { t } = useI18n()
const { MenuItem, trigger } = useContextMenu()
const { toastSuccess } = useMessageToaster()
const { go } = useRouter()

const play = () => trigger(async () => {
  go('queue')
  await playback().queueAndPlay(await playableStore.fetchSongsByGenre(genre.value))
})

const shuffle = () => trigger(async () => {
  go('queue')
  await playback().queueAndPlay(await playableStore.fetchSongsByGenre(genre.value, true))
})

const queue = () => trigger(async () => {
  const songs = await playableStore.fetchSongsByGenre(genre.value)
  queueStore.queue(songs)
  toastSuccess(t('misc.addedToQueue', { count: songs.length, item: pluralize(songs, 'song') }))
})
</script>
