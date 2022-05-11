<template>
  <div v-if="state.showing" id="overlay" :class="state.type" class="overlay">
    <div class="display">
      <SoundBar v-if="state.type === 'loading'"/>
      <i v-if="state.type === 'error'" class="fa fa-exclamation-circle"/>
      <i v-if="state.type === 'warning'" class="fa fa-exclamation-triangle"/>
      <i v-if="state.type === 'info'" class="fa fa-info-circle"/>
      <i v-if="state.type === 'success'" class="fa fa-check-circle"/>

      <span class="message" v-html="state.message"/>
    </div>

    <button v-if="state.dismissible" class="btn-dismiss" type="button" @click.prevent="hide">Close</button>
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

const SoundBar = defineAsyncComponent(() => import('@/components/ui/SoundBar.vue'))

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
