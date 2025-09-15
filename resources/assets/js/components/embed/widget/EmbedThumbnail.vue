<template>
  <article class="relative aspect-square block rounded-md overflow-hidden">
    <img
      :src
      alt="Thumbnail"
      class="w-full aspect-square object-cover"
      loading="lazy"
    >
  </article>
</template>

<script setup lang="ts">
import defaultCover from '@/../img/covers/default.svg'

const props = defineProps<{ embeddable: Embeddable }>()
const { embeddable } = props

let src: string | null = null

switch (embeddable.type) {
  case 'albums':
    src = (embeddable as Album).cover
    break
  case 'artists':
    src = (embeddable as Artist).image
    break
  case 'playlists':
    src = (embeddable as Playlist).cover
    break
  case 'songs':
    src = (embeddable as Song).album_cover
    break
  case 'episodes':
    src = (embeddable as Episode).episode_image
    break
}

src = src || defaultCover
</script>

<style scoped lang="postcss">

</style>
