<template>
  <li :class="{ available: correspondingSong }" :title="tooltip" @click="play">
    <span class="no">{{ index + 1 }}</span>
    <span class="title">{{ track.title }}</span>
    <span class="length">{{ track.fmtLength }}</span>
  </li>
</template>

<script>
import { songStore, queueStore } from '../../stores'
import { playback } from '../../services'

export default {
  name: 'shared--track-list-item',
  props: ['album', 'track', 'index'],

  computed: {
    correspondingSong () {
      return songStore.guess(this.track.title, this.album)
    },

    tooltip () {
      return this.correspondingSong ? 'Click to play' : ''
    }
  },

  methods: {
    play () {
      if (this.correspondingSong) {
        if (!queueStore.contains(this.correspondingSong)) {
          queueStore.queueAfterCurrent(this.correspondingSong)
        }

        playback.play(this.correspondingSong)
      }
    }
  }
}
</script>
