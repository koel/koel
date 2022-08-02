<template>
  <div v-if="state.showing" id="overlay" :class="state.type" class="overlay" data-testid="overlay">
    <div class="display">
      <SoundBars v-if="state.type === 'loading'"/>
      <icon v-if="state.type === 'error'" :icon="faCircleExclamation"/>
      <icon v-if="state.type === 'warning'" :icon="faWarning"/>
      <icon v-if="state.type === 'info'" :icon="faCircleInfo"/>
      <icon v-if="state.type === 'success'" :icon="faCircleCheck"/>

      <span class="message" v-html="state.message"/>
    </div>

    <button v-if="state.dismissible" class="btn-dismiss" type="button" @click.prevent="hide">Close</button>
  </div>
</template>

<script lang="ts" setup>
import { faCircleCheck, faCircleExclamation, faCircleInfo, faWarning } from '@fortawesome/free-solid-svg-icons'
import { eventBus } from '@/utils'
import { defineAsyncComponent, reactive } from 'vue'

const SoundBars = defineAsyncComponent(() => import('@/components/ui/SoundBars.vue'))

const state = reactive<OverlayState>({
  showing: false,
  dismissible: false,
  type: 'loading',
  message: ''
})

const show = (options: Partial<OverlayState>) => {
  Object.assign(state, options)
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
    display: flex;
    align-items: baseline;
    justify-content: center;

    .message {
      margin-left: 6px;
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
