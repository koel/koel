<template>
  <ul>
    <MenuItem @click="play">Play All</MenuItem>
    <MenuItem @click="shuffle">Shuffle All</MenuItem>
    <Separator />
    <MenuItem v-if="allFavorite" @click="undoFavorite">Undo Favorite</MenuItem>
    <MenuItem v-else @click="favorite">Favorite</MenuItem>
    <template v-if="standardAlbums.length && allowDownload">
      <Separator />
      <MenuItem @click="download">Download</MenuItem>
    </template>
    <ContextMenuOfflineItem :playables="albumSongs" />
  </ul>
</template>

<script lang="ts" setup>
import { computed, onMounted, ref, toRef, toRefs } from 'vue'
import { albumStore } from '@/stores/albumStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { useDownload } from '@/composables/useDownload'
import { usePlayCollection } from '@/composables/usePlayCollection'

import ContextMenuOfflineItem from '@/components/ui/context-menu/ContextMenuOfflineItem.vue'

const props = defineProps<{ albums: Album[] }>()
const { albums } = toRefs(props)

const { MenuItem, Separator, trigger } = useContextMenu()
const { playCollection } = usePlayCollection()
const { fromAlbums } = useDownload()

const allowDownload = toRef(commonStore.state, 'allows_download')
const allFavorite = computed(() => albums.value.every(album => album.favorite))
const standardAlbums = computed(() => albums.value.filter(album => !albumStore.isUnknown(album)))

const playSongs = (shuffle: boolean) =>
  trigger(() =>
    playCollection(() => playableStore.fetchSongsForAlbums(albums.value), {
      shuffle,
      emptyMessage: 'The albums are empty.',
    }),
  )

const play = () => playSongs(false)
const shuffle = () => playSongs(true)

const favorite = () => trigger(() => albumStore.favorite(albums.value))
const undoFavorite = () => trigger(() => albumStore.undoFavorite(albums.value))
const download = () => trigger(() => fromAlbums(standardAlbums.value))

const albumSongs = ref<Playable[]>([])

onMounted(async () => {
  albumSongs.value = await playableStore.fetchSongsForAlbums(albums.value)
})
</script>
