<template>
  <li :class="{ available: correspondingSong }" :title="tooltip" @click="play">
    <span class="no">{{ index + 1 }}</span>
    <span class="title">{{ track.title }}</span>
    <a
      :href="iTunesUrl"
      v-if="useiTunes && !correspondingSong"
      target="_blank"
      class="view-on-itunes"
      title="View on iTunes"
      >
      iTunes
    </a>
    <span class="length">{{ track.fmtLength }}</span>
  </li>
</template>

<script>
import { songStore, queueStore, sharedStore } from '../../stores'
import { ls, playback } from '../../services'

export default {
  name: 'shared--track-list-item',
  props: ['album', 'track', 'index'],

  data () {
    return {
      useiTunes: sharedStore.state.useiTunes
    }
  },

  computed: {
    correspondingSong () {
      return songStore.guess(this.track.title, this.album)
    },

    tooltip () {
      return this.correspondingSong ? 'Click to play' : ''
    },

    iTunesUrl () {
      return `/api/itunes/song/${this.album.id}?q=${encodeURIComponent(this.track.title)}&jwt-token=${ls.get('jwt-token')}`
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

<style lang="sass">
a.view-on-itunes {
  display: inline-block;
  border-radius: 3px;
  font-size: .8rem;
  padding: 0 5px;
  color: #fff;
  background: rgba(255, 255, 255, .1);
  height: 20px;
  line-height: 20px;

  &:hover {
    background: linear-gradient(27deg, #fe5c52 0%,#c74bd5 50%,#2daaff 100%);
    color: #fff;
  }

  &:active {
    box-shadow: inset 0px 5px 5px -5px #000;
  }
}
</style>
