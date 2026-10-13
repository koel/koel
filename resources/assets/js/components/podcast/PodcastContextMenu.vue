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
    <ContextMenuRatingItem :rateable="podcast" />
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
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { usePlayCollection } from '@/composables/usePlayCollection'

import ContextMenuRatingItem from '@/components/ui/context-menu/ContextMenuRatingItem.vue'

const props = defineProps<{ podcast: Podcast }>()
const { podcast } = toRefs(props)

const { getRouteParam, go, isCurrentScreen, url } = useRouter()
const { MenuItem, Separator, trigger } = useContextMenu()
const { showConfirmDialog } = useDialogBox()
const { toastSuccess } = useMessageToaster()

const isOnPodcastScreen = computed(() => isCurrentScreen('Podcast') && getRouteParam('id') === podcast.value.id)
const viewDetails = () => trigger(() => go(url('podcasts.show', { id: podcast.value.id })))

const { playCollection } = usePlayCollection()

const playEpisodes = (shuffle: boolean) =>
  trigger(() =>
    playCollection(() => playableStore.fetchEpisodesInPodcast(podcast.value), {
      shuffle,
      emptyMessage: 'The podcast has no episodes.',
    }),
  )

const play = () => playEpisodes(false)
const shuffle = () => playEpisodes(true)

const unsubscribe = () =>
  trigger(async () => {
    if (await showConfirmDialog('Unsubscribe from podcast?')) {
      await podcastStore.unsubscribe(podcast.value)
      toastSuccess('Podcast unsubscribed.')
      eventBus.emit('PODCAST_UNSUBSCRIBED', podcast.value)
    }
  })

const visitWebsite = () => trigger(() => window.open(podcast.value?.link))

const toggleFavorite = () => trigger(() => podcastStore.toggleFavorite(podcast.value))
</script>
