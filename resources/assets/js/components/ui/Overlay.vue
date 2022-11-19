<template>
  <dialog ref="el" :class="state.type" @cancel.prevent="onCancel" data-testid="overlay">
    <div class="wrapper">
      <SoundBars v-if="state.type === 'loading'"/>
      <icon v-if="state.type === 'error'" :icon="faCircleExclamation"/>
      <icon v-if="state.type === 'warning'" :icon="faWarning"/>
      <icon v-if="state.type === 'info'" :icon="faCircleInfo"/>
      <icon v-if="state.type === 'success'" :icon="faCircleCheck"/>

      <span class="message" v-html="state.message"/>
    </div>
  </dialog>
</template>

<script lang="ts" setup>
import { faCircleCheck, faCircleExclamation, faCircleInfo, faWarning } from '@fortawesome/free-solid-svg-icons'
import { defineAsyncComponent, reactive, ref } from 'vue'

const SoundBars = defineAsyncComponent(() => import('@/components/ui/SoundBars.vue'))

const el = ref<HTMLDialogElement>()

const state = reactive<OverlayState>({
  dismissible: false,
  type: 'loading',
  message: ''
})

const show = (options: Partial<OverlayState> = {}) => {
  Object.assign(state, options)
  el.value?.open || el.value?.showModal()
}

const hide = () => el.value?.close()
const onCancel = () => state.dismissible && hide()

defineExpose({ show, hide })
</script>

<style lang="scss" scoped>
dialog {
  border: 0;
  padding: 0;
  background: transparent;

  &::backdrop {
    background: rgba(0, 0, 0, 0.8);
  }

  .wrapper {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 6px;
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
    color: var(--color-orange);
  }
}
</style>
