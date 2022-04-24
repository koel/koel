<template>
  <div class="support-bar" v-if="shown">
    <p>
      Loving Koel? Please consider supporting its development via
      <a href="https://github.com/users/phanan/sponsorship" rel="noopener" target="_blank">GitHub Sponsors</a>
      and/or
      <a href="https://opencollective.com/koel" rel="noopener" target="_blank">OpenCollective</a>.
    </p>
    <button @click.prevent="close">Hide</button>
    <span class="sep"></span>
    <button @click.prevent="stopBugging">Don't bug me again</button>
  </div>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { computed, ref } from 'vue'
import { eventBus } from '@/utils'
import { preferenceStore as preferences } from '@/stores'

const DELAY_UNTIL_SHOWN = 30 * 60 * 1000
let SUPPORT_BAR_TIMEOUT_HANDLE = 0

const shown = ref(false)

const canNag = computed(() => !isMobile.any && !preferences.supportBarNoBugging)

const setUpShowBarTimeout = () => {
  SUPPORT_BAR_TIMEOUT_HANDLE = window.setTimeout(() => (shown.value = true), DELAY_UNTIL_SHOWN)
}

const close = () => {
  shown.value = false
  window.clearTimeout(SUPPORT_BAR_TIMEOUT_HANDLE)
}

const stopBugging = () => {
  preferences.supportBarNoBugging = true
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
