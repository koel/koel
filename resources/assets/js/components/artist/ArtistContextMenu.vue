<template>
  <BaseContextMenu extra-class="artist-menu" ref="base" data-testid="artist-context-menu">
    <template v-if="artist">
      <li data-test="play" @click="play">Play All</li>
      <li data-test="shuffle" @click="shuffle">Shuffle All</li>
      <template v-if="isStandardArtist">
        <li class="separator"></li>
        <li data-test="view-artist" @click="viewArtistDetails">Go to Artist</li>
      </template>
      <template v-if="isStandardArtist && sharedState.allowDownload">
        <li class="separator"></li>
        <li data-test="download" @click="download">Download</li>
      </template>
    </template>
  </BaseContextMenu>
</template>

<script lang="ts" setup>
import { computed, reactive, Ref, toRef } from 'vue'
import { artistStore, sharedStore } from '@/stores'
import { download as downloadService, playback } from '@/services'
import { useContextMenu } from '@/composables'
import router from '@/router'

const { context, base, BaseContextMenu, open, close } = useContextMenu()

const artist = toRef(context, 'artist') as Ref<Artist>

const sharedState = reactive(sharedStore.state)

const isStandardArtist = computed(() =>
  !artistStore.isUnknownArtist(artist.value)
  && !artistStore.isVariousArtists(artist.value)
)

const play = () => playback.playAllByArtist(artist.value)
const shuffle = () => playback.playAllByArtist(artist.value, true /* shuffled */)

const viewArtistDetails = () => {
  router.go(`artist/${artist.value.id}`)
  close()
}

const download = () => {
  downloadService.fromArtist(artist.value)
  close()
}

defineExpose({ open, close })
</script>
