<template>
  <ul>
    <MenuItem @click="play">Play All</MenuItem>
    <MenuItem @click="shuffle">Shuffle All</MenuItem>
    <Separator />
    <MenuItem v-if="allFavorite" @click="undoFavorite">Undo Favorite</MenuItem>
    <MenuItem v-else @click="favorite">Favorite</MenuItem>
    <template v-if="standardArtists.length && allowDownload">
      <Separator />
      <MenuItem @click="download">Download</MenuItem>
    </template>
  </ul>
</template>

<script lang="ts" setup>
import { computed, toRef, toRefs } from 'vue'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { useDownload } from '@/composables/useDownload'
import { usePlayCollection } from '@/composables/usePlayCollection'

const props = defineProps<{ artists: Artist[] }>()
const { artists } = toRefs(props)

const { MenuItem, Separator, trigger } = useContextMenu()
const { playCollection } = usePlayCollection()
const { fromArtists } = useDownload()

const allowDownload = toRef(commonStore.state, 'allows_download')
const allFavorite = computed(() => artists.value.every(artist => artist.favorite))
const standardArtists = computed(() => artists.value.filter(artist => artistStore.isStandard(artist)))

const playSongs = (shuffle: boolean) =>
  trigger(() =>
    playCollection(() => playableStore.fetchSongsForArtists(artists.value), {
      shuffle,
      emptyMessage: 'The artists have no songs.',
    }),
  )

const play = () => playSongs(false)
const shuffle = () => playSongs(true)

const favorite = () => trigger(() => artistStore.favorite(artists.value))
const undoFavorite = () => trigger(() => artistStore.undoFavorite(artists.value))
const download = () => trigger(() => fromArtists(standardArtists.value))
</script>
