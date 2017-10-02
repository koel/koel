<template>
  <span class="volume control" id="volume">
    <i class="fa fa-volume-up" @click.prevent="mute" v-show="!muted"/>
    <i class="fa fa-volume-off" @click.prevent="unmute" v-show="muted"/>
    <input type="range" id="volumeRange" max="10" step="0.1" 
      @change="broadcastVolume" class="plyr__volume"
      @input="setVolume"
    >
  </span>
</template>

<script>
import { playback, socket } from '../../services'

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
    },

    /**
     * Set the volume.
     *
     * @param {Event} e
     */
    setVolume (e) {
      playback.setVolume(e.target.value)
      this.muted = e.target.value === 0
    },

    /**
     * Broadcast the volume changed event to remote controller.
     *
     * @param  {Event} e
     */
    broadcastVolume (e) {
      socket.broadcast('volume:changed', e.target.value)
    }
  }
}
</script>

<style lang="scss">
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
