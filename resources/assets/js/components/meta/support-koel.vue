<template>
  <div class="support-bar" v-if="shown">
    <p>
      Loving Koel? Please consider supporting its development via
      <a href="https://github.com/users/phanan/sponsorship" target="_blank" rel="noopener">
        GitHub Sponsors
      </a>
      and/or
      <a href="https://opencollective.com/koel" target="_blank" rel="noopener">OpenCollective</a>.
    </p>
    <button @click.prevent="close">Hide</button>
    <span class="sep"></span>
    <button @click.prevent="stopBugging">Don't bug me again</button>
  </div>
</template>

<script lang="ts">
import isMobile from 'ismobilejs'
import Vue from 'vue'
import { eventBus } from '@/utils'
import { preferenceStore as preferences } from '@/stores'

const DELAY_UNTIL_SHOWN = 30 * 60 * 1000
let SUPPORT_BAR_TIMEOUT_HANDLE = 0

export default Vue.extend({
  data: () => ({
    shown: false
  }),

  computed: {
    canNag (): boolean {
      return !isMobile.any && !preferences.supportBarNoBugging
    }
  },

  created (): void {
    eventBus.on({
      'KOEL_READY': (): void => {
        if (this.canNag) {
          this.setUpShowBarTimeout()
        }
      }
    })
  },

  methods: {
    setUpShowBarTimeout (): void {
      SUPPORT_BAR_TIMEOUT_HANDLE = window.setTimeout(() => (this.shown = true), DELAY_UNTIL_SHOWN)
    },

    close (): void {
      this.shown = false
      window.clearTimeout(SUPPORT_BAR_TIMEOUT_HANDLE)
    },

    stopBugging (): void {
      preferences.supportBarNoBugging = true
      this.close()
    }
  }
})
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
