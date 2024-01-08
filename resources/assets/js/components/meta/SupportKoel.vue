<template>
  <div v-if="shown" class="support-bar" data-testid="support-bar">
    <p>
      Loving Koel? Please consider supporting its development via
      <a href="https://github.com/users/phanan/sponsorship" rel="noopener" target="_blank">GitHub Sponsors</a>
      and/or
      <a href="https://opencollective.com/koel" rel="noopener" target="_blank">OpenCollective</a>.
    </p>
    <button type="button" @click.prevent="close">Hide</button>
    <span class="sep" />
    <button type="button" @click.prevent="stopBugging">
      Don't bug me again
    </button>
  </div>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { ref, watch } from 'vue'
import { preferenceStore } from '@/stores'
import { useKoelPlus } from '@/composables'

const delayUntilShow = 30 * 60 * 1000 // 30 minutes

const shown = ref(false)

const { isPlus } = useKoelPlus()
const setUpShowBarTimeout = () => setTimeout(() => (shown.value = true), delayUntilShow)
const close = () => shown.value = false

const stopBugging = () => {
  preferenceStore.set('supportBarNoBugging', true)
  close()
}

watch(preferenceStore.initialized, initialized => {
  if (!initialized) return
  if (preferenceStore.state.supportBarNoBugging || isMobile.any) return
  if (!isPlus.value) return

  setUpShowBarTimeout()
}, { immediate: true })
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
