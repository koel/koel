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

    <button class="btn-dismiss" v-if="state.dismissible" @click.prevent="hide">Close</button>
  </div>
</template>

<script lang="ts" setup>
import { assign } from 'lodash'
import { eventBus } from '@/utils'
import { defineAsyncComponent, reactive } from 'vue'

export type OverlayState = {
  showing: boolean
  dismissible: boolean
  type: 'loading' | 'success' | 'info' | 'warning' | 'error'
  message: string
}

const SoundBar = defineAsyncComponent(() => import('@/components/ui/sound-bar.vue'))

const state = reactive<OverlayState>({
  showing: true,
  dismissible: false,
  type: 'loading',
  message: ''
})

const show = (options: Partial<OverlayState>) => {
  assign(state, options)
  state.showing = true
}

const hide = () => (state.showing = false)

eventBus.on({
  'SHOW_OVERLAY': show,
  'HIDE_OVERLAY': hide
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
