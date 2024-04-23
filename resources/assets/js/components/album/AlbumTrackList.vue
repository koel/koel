<template>
  <article>
    <h3 class="text-2xl mb-3">Track Listing</h3>

    <ul>
      <li
        v-for="(track, index) in tracks"
        :key="index"
        class="flex p-2 before:w-7 before:opacity-50"
        data-testid="album-track-item"
      >
        <TrackListItem :album="album" :track="track" />
      </li>
    </ul>
  </article>
</template>

<script lang="ts" setup>
import { onMounted, provide, ref, toRefs } from 'vue'
import { songStore } from '@/stores'
import { SongsKey } from '@/symbols'

import TrackListItem from '@/components/album/AlbumTrackListItem.vue'

const props = defineProps<{ album: Album, tracks: AlbumTrack[] }>()
const { album, tracks } = toRefs(props)

const songs = ref<Song[]>([])

// @ts-ignore
provide(SongsKey, songs)

onMounted(async () => songs.value = await songStore.fetchForAlbum(album.value))
</script>

<style lang="postcss" scoped>
ul {
  counter-reset: trackCounter;
}

li {
  counter-increment: trackCounter;

  &::before {
    content: counter(trackCounter);
  }

  &:nth-child(even) {
    background: rgba(255, 255, 255, 0.05);
  }
}
</style>
