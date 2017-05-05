<template>
  <span class="view-modes">
    <a class="thumbnails" :class="{ active: mutatedMode === 'thumbnails' }"
      title="View as thumbnails"
      @click.prevent="setMode('thumbnails')"><i class="fa fa-th-large"></i></a>
    <a class="list" :class="{ active: mutatedMode === 'list' }"
      title="View as list"
      @click.prevent="setMode('list')"><i class="fa fa-list"></i></a>
  </span>
</template>

<script>
import isMobile from 'ismobilejs'

import { event } from '../../utils'
import { preferenceStore as preferences } from '../../stores'

export default {
  props: ['mode', 'for'],

  data () {
    return {
      mutatedMode: this.mode
    }
  },

  computed: {
    /**
     * The preference key for local storage for persistent mode.
     *
     * @return {string}
     */
    preferenceKey () {
      return `${this.for}ViewMode`
    }
  },

  methods: {
    setMode (mode) {
      preferences[this.preferenceKey] = this.mutatedMode = mode
      this.$parent.changeViewMode(mode)
    }
  },

  created () {
    event.on('koel:ready', () => {
      this.mutatedMode = preferences[this.preferenceKey]

      // If the value is empty, we set a default mode.
      // On mobile, the mode should be 'listing'.
      // For desktop, 'thumbnails'.
      if (!this.mutatedMode) {
        this.mutatedMode = isMobile.phone ? 'list' : 'thumbnails'
      }

      this.setMode(this.mutatedMode)
    })
  }
}
</script>

<style lang="scss" scoped>
@import "../../../sass/partials/_vars.scss";
@import "../../../sass/partials/_mixins.scss";

.view-modes {
  display: flex;
  flex: 0 0 64px;
  border: 1px solid rgba(255, 255, 255, .2);
  height: 2rem;
  border-radius: 5px;
  overflow: hidden;

  a {
    width: 50%;
    text-align: center;
    line-height: 2rem;
    font-size: 1rem;

    &.active {
      background: #fff;
      color: #111;
    }
  }

  @media only screen and(max-width: 768px) {
    flex: auto;
    width: 64px;
    margin-top: 8px;
  }
}
</style>
