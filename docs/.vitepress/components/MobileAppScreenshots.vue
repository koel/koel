<template>
  <div>
    <img class="rounded-2xl" :src="homeScreenshot" alt="Home screen">
    <img class="rounded-2xl" :src="artistsScreenshot" alt="Artist screen">
    <img class="rounded-2xl" :src="nowPlayingScreenshot" alt="Now playing screen">
    <img class="rounded-2xl" :src="downloadedScreenshot" alt="Downloaded screen">
  </div>
</template>

<script lang="ts" setup>
import homeScreenshot from '../../assets/img/mobile/home.webp'
import artistsScreenshot from '../../assets/img/mobile/artists.webp'
import nowPlayingScreenshot from '../../assets/img/mobile/now-playing.webp'
import downloadedScreenshot from '../../assets/img/mobile/downloaded.webp'
</script>


<style lang="postcss" scoped>
div {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  grid-column-gap: 12px;
  padding: 1rem 0;

  img:nth-child(2), img:nth-child(4) {
    margin-top: -10%;
  }
}
</style>
