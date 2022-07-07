<template>
  <div v-if="shown" class="support-bar" data-testid="support-bar">
    <p>
      Loving Koel? Please consider supporting its development via
      <a href="https://github.com/users/phanan/sponsorship" rel="noopener" target="_blank">GitHub Sponsors</a>
      and/or
      <a href="https://opencollective.com/koel" rel="noopener" target="_blank">OpenCollective</a>.
    </p>
    <button data-testid="hide-support-koel" type="button" @click.prevent="close">Hide</button>
    <span class="sep"></span>
    <button data-testid="stop-support-koel-bugging" @click.prevent="stopBugging" type="button">
      Don't bug me again
    </button>
  </div>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { computed, ref, toRef } from 'vue'
import { eventBus } from '@/utils'
import { preferenceStore } from '@/stores'

const delayUntilShow = 30 * 60 * 1000
let timeoutHandle = 0

const shown = ref(false)
const noBugging = toRef(preferenceStore.state, 'supportBarNoBugging')

const canNag = computed(() => !isMobile.any && !noBugging.value)

const setUpShowBarTimeout = () => (timeoutHandle = window.setTimeout(() => (shown.value = true), delayUntilShow))

const close = () => {
  shown.value = false
  window.clearTimeout(timeoutHandle)
}

const stopBugging = () => {
  preferenceStore.set('supportBarNoBugging', true)
  close()
}

eventBus.on('KOEL_READY', () => canNag.value && setUpShowBarTimeout())
</script>

<style lang="scss" scoped>
.support-bar {
  background: var(--color-bg-primary);
  font-size: .9rem;
  padding: .75rem 1rem;
  display: flex;
  color: rgba(255, 255, 255, .6);
  z-index: 9;

  > * + * {
    margin-left: 1rem;
  }

  p {
    flex: 1;
  }

  a {
    color: var(--color-text-primary);

    &:hover {
      color: var(--color-highlight);
    }
  }

  .sep {
    display: block;

    &::after {
      content: '•';
      display: block;
    }
  }

  button {
    color: var(--color-text-primary);
    font-size: .9rem;

    &:hover {
      color: var(--color-highlight);
    }
  }
}
</style>
