<template>
  <article class="track-listing">
    <h3>Track Listing</h3>

    <ul class="tracks">
      <li v-for="(track, index) in tracks" :key="index" data-testid="album-track-item">
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
article {
  h3 {
    font-size: 1.4rem;
    margin-bottom: 1rem;
  }

  ul {
    counter-reset: trackCounter;
  }

  li {
    counter-increment: trackCounter;
    display: flex;
    padding: 8px;

    &::before {
      content: counter(trackCounter);
      flex: 0 0 24px;
      opacity: .5;
    }

    &:nth-child(even) {
      background: rgba(255, 255, 255, 0.05);
    }
  }
}
</style>
