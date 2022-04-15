<template>
  <li :class="{ available: song }" :title="tooltip" @click="play" role="button" tabindex="0">
    <span class="no">{{ index + 1 }}</span>
    <span class="title">{{ track.title }}</span>
    <a
      :href="iTunesUrl"
      v-if="useiTunes && !song"
      target="_blank"
      class="view-on-itunes"
      title="View on iTunes"
    >
      iTunes
    </a>
    <span class="length">{{ track.fmtLength }}</span>
  </li>
</template>

<script lang="ts">
import Vue, { PropOptions } from 'vue'
import { songStore, queueStore, sharedStore } from '@/stores'
import { auth, playback } from '@/services'

export default Vue.extend({
  props: {
    album: {
      type: Object,
      required: true
    } as PropOptions<Album>,

    track: {
      type: Object,
      required: true
    } as PropOptions<AlbumTrack>,

    index: {
      type: Number,
      required: true
    }
  },

  data: () => ({
    useiTunes: sharedStore.state.useiTunes
  }),

  computed: {
    song (): Song | null {
      return songStore.guess(this.track.title, this.album)
    },

    tooltip (): string {
      return this.song ? 'Click to play' : ''
    },

    iTunesUrl (): string {
      return `${window.BASE_URL}itunes/song/${this.album.id}?q=${encodeURIComponent(this.track.title)}&api_token=${auth.getToken()}`
    }
  },

  methods: {
    play (): void {
      if (this.song) {
        queueStore.contains(this.song) || queueStore.queueAfterCurrent(this.song)
        playback.play(this.song)
      }
    }
  }
})
</script>

<style lang="scss" scoped>
[role=button] {
  &:focus {
    span.title {
      color: var(--color-highlight);
    }
  }

  a.view-on-itunes {
    display: inline-block;
    border-radius: 3px;
    font-size: .8rem;
    padding: 0 5px;
    color: var(--color-text-primary);
    background: rgba(255, 255, 255, .1);
    height: 20px;
    line-height: 20px;
    margin-left: 4px;

    &:hover, &:focus {
      background: linear-gradient(27deg, #fe5c52 0%,#c74bd5 50%,#2daaff 100%);
      color: var(--color-text-primary);
    }

    &:active {
      box-shadow: inset 0px 5px 5px -5px #000;
    }
  }
}
</style>
