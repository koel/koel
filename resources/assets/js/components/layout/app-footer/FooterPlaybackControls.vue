<template>
  <div class="playback-controls" data-testid="footer-middle-pane">
    <div class="buttons">
      <LikeButton v-if="song" :song="song" class="like-btn" />
      <button v-else type="button" /> <!-- a placeholder to maintain the flex layout -->

      <button type="button" title="Play previous song" @click.prevent="playPrev">
        <Icon :icon="faStepBackward" />
      </button>

      <PlayButton />

      <button type="button" title="Play next song" @click.prevent="playNext">
        <Icon :icon="faStepForward" />
      </button>

      <RepeatModeSwitch class="repeat-mode-btn" />
    </div>
  </div>
</template>

<script lang="ts" setup>
import { faStepBackward, faStepForward } from '@fortawesome/free-solid-svg-icons'
import { ref } from 'vue'
import { playbackService } from '@/services'
import { requireInjection } from '@/utils'
import { CurrentSongKey } from '@/symbols'

import RepeatModeSwitch from '@/components/ui/RepeatModeSwitch.vue'
import LikeButton from '@/components/song/SongLikeButton.vue'
import PlayButton from '@/components/ui/FooterPlayButton.vue'

const song = requireInjection(CurrentSongKey, ref())

const playPrev = async () => await playbackService.playPrev()
const playNext = async () => await playbackService.playNext()
</script>

<style lang="postcss" scoped>
.playback-controls {
  flex: 1;
  display: flex;
  flex-direction: column;
  place-content: center;
  place-items: center;

  :fullscreen & {
    transform: scale(1.2);
  }
}

.buttons {
  color: var(--color-text-secondary);
  display: flex;
  place-content: center;
  place-items: center;
  gap: 2rem;

  @media screen and (max-width: 768px) {
    gap: .75rem;
  }

  button {
    color: currentColor;
    font-size: 1.5rem;
    width: 2.5rem;
    aspect-ratio: 1/1;
    transition: all .2s ease-in-out;
    transition-property: color, border, transform;

    &:hover {
      color: var(--color-text-primary);
    }

    &.like-btn, &.repeat-mode-btn {
      font-size: 1rem;
    }
  }
}
</style>
