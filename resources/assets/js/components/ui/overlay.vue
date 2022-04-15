<template>
  <div id="overlay" v-if="state.showing" class="overlay" :class="state.type">
    <div class="display">
      <sound-bar v-if="state.type === 'loading'"/>
      <i class="fa fa-exclamation-circle" v-if="state.type === 'error'"></i>
      <i class="fa fa-exclamation-triangle" v-if="state.type === 'warning'"></i>
      <i class="fa fa-info-circle" v-if="state.type === 'info'"></i>
      <i class="fa fa-check-circle" v-if="state.type === 'success'"></i>

      <span class="message" v-html="state.message"></span>
    </div>

    <button class="btn-dismiss" v-if="state.dismissible" @click.prevent="state.showing = false">Close</button>
  </div>
</template>

<script lang="ts">
import Vue from 'vue'
import { assign } from 'lodash'
import { eventBus } from '@/utils'

export default Vue.extend({
  components: {
    SoundBar: () => import('@/components/ui/sound-bar.vue')
  },

  data: () => ({
    state: {
      showing: true,
      dismissible: false,
      /**
       * Either 'loading', 'success', 'info', 'warning', or 'error'.
       * This dictates the icon as well as possibly other visual appearances.
       */
      type: 'loading',
      message: ''
    }
  }),

  methods: {
    show (options: object): void {
      assign(this.state, options)
      this.state.showing = true
    },

    hide (): void {
      this.state.showing = false
    }
  },

  created (): void {
    eventBus.on({
      'SHOW_OVERLAY': (options: object): void => this.show(options),
      'HIDE_OVERLAY': (): void => this.hide()
    })
  }
})
</script>

<style lang="scss">
#overlay {
  background-color: var(--color-bg-primary);
  flex-direction: column;

  .display {
    @include vertical-center();

    i {
      margin-right: 6px;
    }
  }

  &.error {
    color: var(--color-red);
  }

  &.success {
    color: var(--color-green);
  }

  &.info {
    color: var(--color-blue);
  }

  &.loading {
    color: var(--color-text-secondary);
  }

  &.warning {
    color: var(--color-highlight);
  }
}
</style>
