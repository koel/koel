<template>
  <div :style="{ backgroundImage: thumbnailUrl ? `url(${thumbnailUrl})` : 'none' }" data-testid="album-art-overlay"/>
</template>

<script lang="ts">
import Vue, { PropOptions } from 'vue'
import { albumStore } from '@/stores'

export default Vue.extend({
  props: {
    song: {
      type: Object
    } as PropOptions<Song | null>
  },

  data: () => ({
    thumbnailUrl: null as string | null
  }),

  watch: {
    song: {
      immediate: true,
      async handler (): Promise<void> {
        if (this.song) {
          try {
            this.thumbnailUrl = await albumStore.getThumbnail(this.song.album)
          } catch (e) {
            this.thumbnailUrl = null
          }
        }
      }
    }
  }
})
</script>

<style scoped>
div {
  position: fixed;
  opacity: .1;
  z-index: 10000;
  overflow: hidden;
  background-size: cover;
  background-position: center;
  pointer-events: none;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}
</style>
