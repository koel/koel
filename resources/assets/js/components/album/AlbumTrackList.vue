<template>
  <section class="track-listing">
    <h1>Track Listing</h1>

    <ul class="tracks">
      <li
        is="vue:TrackListItem"
        v-for="(track, index) in album.info.tracks"
        :key="index"
        :album="album"
        :track="track"
      />
    </ul>
  </section>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, toRefs } from 'vue'

const TrackListItem = defineAsyncComponent(() => import('./AlbumTrackListItem.vue'))

const props = defineProps<{ album: Album }>()
const { album } = toRefs(props)
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
