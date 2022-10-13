<template>
  <footer id="mainFooter" @contextmenu.prevent="requestContextMenu">
    <AudioPlayer/>

    <div class="wrapper">
      <SongInfo/>
      <PlaybackControls/>
      <ExtraControls/>
    </div>
  </footer>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { eventBus, requireInjection } from '@/utils'
import { CurrentSongKey } from '@/symbols'

import AudioPlayer from '@/components/layout/app-footer/AudioPlayer.vue'
import SongInfo from '@/components/layout/app-footer/FooterSongInfo.vue'
import ExtraControls from '@/components/layout/app-footer/FooterExtraControls.vue'
import PlaybackControls from '@/components/layout/app-footer/FooterPlaybackControls.vue'

const song = requireInjection(CurrentSongKey, ref(null))

const requestContextMenu = (event: MouseEvent) => {
  song.value && eventBus.emit('SONG_CONTEXT_MENU_REQUESTED', event, song.value)
}
</script>

<style lang="scss" scoped>
footer {
  background: var(--color-bg-secondary);
  height: var(--footer-height);
  display: flex;
  box-shadow: 0 0 30px 20px rgba(0, 0, 0, .2);
  flex-direction: column;
  position: relative;
  z-index: 1;

  .wrapper {
    display: flex;
    flex: 1;
  }
}
</style>
