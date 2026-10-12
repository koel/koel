<template>
  <ul>
    <MenuItem @click="play">Play All</MenuItem>
    <MenuItem @click="shuffle">Shuffle All</MenuItem>
    <Separator />
    <template v-if="!isOnPodcastScreen">
      <MenuItem @click="viewDetails">View Details</MenuItem>
      <Separator />
    </template>
    <MenuItem @click="toggleFavorite">{{ podcast.favorite ? 'Undo Favorite' : 'Favorite' }}</MenuItem>
    <Separator />
    <li
      tabindex="-1"
      class="px-4 py-2 focus:outline-hidden"
      @mouseover="($event.currentTarget as HTMLLIElement).focus()"
    >
      <StarRating :rateable="podcast" @rate="closeContextMenu" />
    </li>
    <Separator />
    <MenuItem @click="visitWebsite">Visit Website</MenuItem>
    <Separator />
    <MenuItem @click="unsubscribe">Unsubscribe</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { playableStore } from '@/stores/playableStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { useRouter } from '@/composables/useRouter'
import { eventBus } from '@/utils/eventBus'
import { podcastStore } from '@/stores/podcastStore'
import { playback } from '@/services/playbackManager'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'

import StarRating from '@/components/ui/StarRating.vue'

const props = defineProps<{ podcast: Podcast }>()
const { podcast } = toRefs(props)

const { getRouteParam, go, isCurrentScreen, url } = useRouter()
const { MenuItem, Separator, closeContextMenu, trigger } = useContextMenu()
const { showConfirmDialog } = useDialogBox()
const { toastSuccess } = useMessageToaster()

const isOnPodcastScreen = computed(() => isCurrentScreen('Podcast') && getRouteParam('id') === podcast.value.id)
const viewDetails = () => trigger(() => go(url('podcasts.show', { id: podcast.value.id })))

const play = () =>
  trigger(async () => {
    playback().queueAndPlay(await playableStore.fetchEpisodesInPodcast(podcast.value))
    go(url('queue'))
  })

const shuffle = () =>
  trigger(async () => {
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
