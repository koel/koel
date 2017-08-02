<template>
  <span class="volume control" id="volume">
    <i class="fa fa-volume-up" @click.prevent="mute" v-show="!muted"/>
    <i class="fa fa-volume-off" @click.prevent="unmute" v-show="muted"/>
    <input type="range" id="volumeRange" max="10" step="0.1" @change="muted = false" class="plyr__volume">
  </span>
</template>

<script>
import { playback } from '../../services'

export default {
  data () {
    return {
      muted: false
    }
  },

  methods: {
    /**
     * Mute the volume.
     */
    mute () {
      this.muted = true
      return playback.mute()
    },

    /**
     * Unmute the volume.
     */
    unmute () {
      this.muted = false
      return playback.unmute()
    }
  }
}
</script>

<style lang="sass">
@import "../../../sass/partials/_vars.scss";
@import "../../../sass/partials/_mixins.scss";

#volume {
  @include vertical-center();

  // More tweaks
  input[type=range] {
    margin-top: -3px;
  }

  i {
    width: 16px;
  }

  @media only screen and (max-width: 768px) {
    display: none !important;
  }
}
</style>
