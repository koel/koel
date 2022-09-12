<template>
  <footer id="mainFooter" @contextmenu.prevent="requestContextMenu">
    <PlayerControls :song="song"/>

    <div class="media-info-wrap">
      <MiddlePane :song="song"/>
      <ExtraControls :song="song"/>
    </div>
  </footer>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { eventBus } from '@/utils'

import ExtraControls from '@/components/layout/app-footer/FooterExtraControls.vue'
import MiddlePane from '@/components/layout/app-footer/FooterMiddlePane.vue'
import PlayerControls from '@/components/layout/app-footer/FooterPlayerControls.vue'

const song = ref<Song | null>(null)
const viewingQueue = ref(false)

const requestContextMenu = (event: MouseEvent) => {
  song.value?.id && eventBus.emit('SONG_CONTEXT_MENU_REQUESTED', event, song.value)
}

eventBus.on({
  SONG_STARTED: (newSong: Song) => (song.value = newSong),
  ACTIVATE_SCREEN: (screen: ScreenName) => (viewingQueue.value = screen === 'Queue')
})
</script>

<style lang="scss" scoped>
footer {
  background: var(--color-bg-secondary);
  height: var(--footer-height);
  display: flex;
  position: relative;
  z-index: 99;

  .media-info-wrap {
    flex: 1;
    display: flex;
  }

  // Add a reverse gradient here to eliminate the "hard cut" feel.
  &::before {
    content: " ";
    position: absolute;
    width: 100%;
    height: calc(2 * var(--footer-height) / 3);
    bottom: var(--footer-height);
    left: 0;

    // Safari 8 won't recognize rgba(255, 255, 255, 0) and treat it as black.
    // rgba(#000, 0) is a workaround.
    background-image: linear-gradient(to bottom, rgba(#000, 0) 0%, rgba(#000, .1) 100%);
    pointer-events: none; // click-through
  }

  @media only screen and (max-width: 768px) {
    @include themed-background();
    height: var(--footer-height-mobile);
    padding-top: 12px; // leave space for the audio track
  }
}
</style>
