<template>
  <ul>
    <MenuItem @click="play">Play All</MenuItem>
    <MenuItem @click="shuffle">Shuffle All</MenuItem>
    <Separator />
    <MenuItem @click="toggleFavorite">{{ podcast.favorite ? 'Undo Favorite' : 'Favorite' }}</MenuItem>
    <Separator />
    <MenuItem @click="visitWebsite">Visit Website</MenuItem>
    <Separator />
    <MenuItem @click="unsubscribe">Unsubscribe</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'
import { playableStore } from '@/stores/playableStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { useRouter } from '@/composables/useRouter'
import { eventBus } from '@/utils/eventBus'
import { podcastStore } from '@/stores/podcastStore'
import { playback } from '@/services/playbackManager'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'

const props = defineProps<{ podcast: Podcast }>()
const { podcast } = toRefs(props)

const { go, url } = useRouter()
const { MenuItem, Separator, trigger } = useContextMenu()
const { showConfirmDialog } = useDialogBox()
const { toastSuccess } = useMessageToaster()

const play = () => trigger(async () => {
  playback().queueAndPlay(await playableStore.fetchEpisodesInPodcast(podcast.value))
  go(url('queue'))
})

const shuffle = () => trigger(async () => {
  playback().queueAndPlay(await playableStore.fetchEpisodesInPodcast(podcast.value), true)
  go(url('queue'))
})

const unsubscribe = async () => {
  if (await showConfirmDialog('Unsubscribe from podcast?')) {
    await podcastStore.unsubscribe(podcast.value)
    toastSuccess('Podcast unsubscribed.')
    eventBus.emit('PODCAST_UNSUBSCRIBED', podcast.value)
  }
}

const visitWebsite = () => trigger(() => window.open(podcast.value?.link))

const toggleFavorite = () => trigger(() => podcastStore.toggleFavorite(podcast.value))
</script>
