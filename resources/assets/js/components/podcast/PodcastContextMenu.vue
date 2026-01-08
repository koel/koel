<template>
  <ul>
    <MenuItem @click="play">{{ $t('menu.podcast.playAll') }}</MenuItem>
    <MenuItem @click="shuffle">{{ $t('menu.podcast.shuffleAll') }}</MenuItem>
    <Separator />
    <MenuItem @click="toggleFavorite">{{ podcast.favorite ? $t('menu.podcast.undoFavorite') : $t('menu.podcast.favorite') }}</MenuItem>
    <Separator />
    <MenuItem @click="visitWebsite">{{ $t('menu.podcast.visitWebsite') }}</MenuItem>
    <Separator />
    <MenuItem @click="unsubscribe">{{ $t('menu.podcast.unsubscribe') }}</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'
import { useI18n } from 'vue-i18n'
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

const { t } = useI18n()
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
  if (await showConfirmDialog(t('menu.podcast.unsubscribeConfirm'))) {
    await podcastStore.unsubscribe(podcast.value)
    toastSuccess('Podcast unsubscribed.')
    eventBus.emit('PODCAST_UNSUBSCRIBED', podcast.value)
  }
}

const visitWebsite = () => trigger(() => window.open(podcast.value?.link))

const toggleFavorite = () => trigger(() => podcastStore.toggleFavorite(podcast.value))
</script>
