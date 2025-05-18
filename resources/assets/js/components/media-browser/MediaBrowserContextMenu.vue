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
import { playbackService } from '@/services/playbackService'
import { queueStore } from '@/stores/queueStore'
import type { ResolvableData } from '@/services/mediaBrowser'
import { mediaBrowser } from '@/services/mediaBrowser'
import { pluralize } from '@/utils/formatters'

const { base, ContextMenu, open, trigger } = useContextMenu()
const { go, url } = useRouter()
const { toastWarning, toastSuccess } = useMessageToaster()

const items = ref<Array<Folder | Song>>()

const isForSingleFolder = computed(() => {
  return items.value?.length === 1 && items.value[0].type === 'folders'
})

const idsAndFolderPaths = computed(() => {
  return items.value?.map<ResolvableData>(item => {
    if (item.type === 'folders') {
      return { type: 'folders', path: item.path }
    } else {
      return { type: 'songs', id: item.id }
    }
  })
})

const openFolder = () => trigger(async () => go(url('media-browser', { path: (items.value![0] as Folder).path })))

const play = () => trigger(async () => {
  const songs = await mediaBrowser.resolveSongsFromIdsAndFolderPaths(idsAndFolderPaths.value!)

  if (songs.length) {
    playbackService.queueAndPlay(songs)
    go(url('queue'))
  } else {
    toastWarning('Nothing to play.')
  }
})

const shuffle = () => trigger(async () => {
  const songs = await mediaBrowser.resolveSongsFromIdsAndFolderPaths(idsAndFolderPaths.value!, true)

  if (songs.length) {
    // folder shuffling has already been done server-side, but local songs shuffling still needs to be done
    playbackService.queueAndPlay(songs, true)
    go(url('queue'))
  } else {
    toastWarning('Nothing to play.')
  }
})

const addToQueue = () => trigger(async () => {
  const songs = await mediaBrowser.resolveSongsFromIdsAndFolderPaths(idsAndFolderPaths.value!)

  if (songs.length) {
    queueStore.queueAfterCurrent(songs)
    toastSuccess(`${pluralize(songs, 'song')} added to queue.`)
  } else {
    toastWarning('The folder is empty.')
  }
})

eventBus.on('MEDIA_BROWSER_CONTEXT_MENU_REQUESTED', async ({ pageX, pageY }, _items) => {
  items.value = _items
  await open(pageY, pageX)
})
</script>
