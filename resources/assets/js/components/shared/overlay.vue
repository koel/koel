<template>
  <div id="overlay" v-show="state.showing" :class="state.type">
    <div class="display">
      <sound-bar v-show="state.type === 'loading'"/>
      <i class="fa fa-exclamation-circle" v-show="state.type === 'error'"/>
      <i class="fa fa-exclamation-triangle" v-show="state.type === 'warning'"/>
      <i class="fa fa-info-circle" v-show="state.type === 'info'"/>
      <i class="fa fa-check-circle" v-show="state.type === 'success'"/>

      <span v-html="state.message"/>
    </div>

    <button v-show="state.dismissable" @click.prevent="state.showing = false">Close</button>
  </div>
</template>

<script>
import { assign } from 'lodash';

import { event } from '../../utils';
import soundBar from './sound-bar.vue';

export default {
  components: { soundBar },

  data() {
    return {
      state: {
        showing: true,
        dismissable: false,
        /**
         * Either 'loading', 'success', 'info', 'warning', or 'error'.
         * This dictates the icon as well as possibly other visual appearances.
         *
         * @type {String}
         */
        type: 'loading',
        message: '',
      },
    };
  },

  methods: {
    /**
     * Shows the overlay.
     *
     * @param {String}  message   The message to display.
     * @param {String}  type    (loading|success|info|warning|error)
     * @param {Boolean} dismissable Whether to show the Close button
     */
    show(options) {
      assign(this.state, options);
      this.state.showing = true;
    },

    /**
     * Hide the overlay.
     */
    hide() {
      this.state.showing = false;
    },

    /**
     * Set the overlay to be dismissable (or not).
     * A Close button will be shown/hidden correspondingly.
     *
     * @param {Boolean} dismissable
     */
    setDimissable(dismissable = true) {
      this.state.dismissable = dismissable;
    },
  },

  created() {
    event.on({
      'overlay:show': options => this.show(options),
      'overlay:hide': () => this.hide(),
    });
  },
};
</script>

<style lang="sass">
@import "../../../sass/partials/_vars.scss";
@import "../../../sass/partials/_mixins.scss";

#overlay {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 9999;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 1);

  @include vertical-center();
  flex-direction: column;

  .display {
    @include vertical-center();

    i {
      margin-right: 6px;
    }
  }

  button {
    margin-top: 16px;
  }

  &.error {
    color: $colorRed;
  }

  &.success {
    color: $colorGreen;
  }

  &.info {
    color: $colorBlue;
  }

  &.loading {
    color: $color2ndText;
  }

  &.warning {
    color: $colorOrange;
  }
}
</style>
