<template>
  <section class="track-listing">
    <h1>Track Listing</h1>

    <ul class="tracks">
      <li
        is="vue:TrackListItem"
        v-for="(track, index) in album.info?.tracks"
        :key="index"
        :album="album"
        :track="track"
        data-testid="album-track-item"
      />
    </ul>
  </section>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, onMounted, provide, ref, toRefs } from 'vue'
import { songStore } from '@/stores'
import { SongsKey } from '@/symbols'

const TrackListItem = defineAsyncComponent(() => import('./AlbumTrackListItem.vue'))

const props = defineProps<{ album: Album }>()
const { album } = toRefs(props)

const songs = ref<Song[]>([])

provide(SongsKey, songs)

onMounted(async () => songs.value = await songStore.fetchForAlbum(album.value))
</script>

<style lang="scss" scoped>
ul {
  counter-reset: trackCounter;
}

li {
  counter-increment: trackCounter;

  &::before {
    content: counter(trackCounter);
  }
}
</style>
