<template>
  <div v-koel-focus class="about text-secondary" data-testid="about-modal" tabindex="0" @keydown.esc="close">
    <header>
      <h1 class="text-white">About Koel</h1>
    </header>

    <main>
      <div class="logo">
        <img alt="Koel's logo" src="@/../img/logo.svg" width="128">
      </div>

      <p class="current-version">{{ commonStore.state.currentVersion }}</p>

      <p v-if="shouldDisplayVersionUpdate && hasNewVersion" class="new-version">
        <a :href="latestVersionUrl" target="_blank">
          A new Koel version is available ({{ commonStore.state.latestVersion }}).
        </a>
      </p>

      <p class="author">
        Made with ❤️ by
        <a href="https://github.com/phanan" rel="noopener" target="_blank">Phan An</a>
        and quite a few
        <a href="https://github.com/koel/core/graphs/contributors" rel="noopener" target="_blank">awesome</a>&nbsp;
        <a href="https://github.com/koel/koel/graphs/contributors" rel="noopener" target="_blank">contributors</a>.
      </p>

      <p v-if="isDemo" class="demo-credits">
        Demo music provided by
        <a href="https://www.bensound.com" rel="noopener" target="_blank">Bensound</a>.
      </p>

      <p>
        Loving Koel? Please consider supporting its development via
        <a href="https://github.com/users/phanan/sponsorship" rel="noopener" target="_blank">GitHub Sponsors</a>
        and/or
        <a href="https://opencollective.com/koel" rel="noopener" target="_blank">OpenCollective</a>.
      </p>
    </main>

    <footer>
      <Btn data-test="close-modal-btn" red rounded @click.prevent="close">Close</Btn>
    </footer>
  </div>
</template>

<script lang="ts" setup>
import compareVersions from 'compare-versions'
import { defineAsyncComponent } from 'vue'
import { commonStore, userStore } from '@/stores'
import { isDemo } from '@/utils'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))

const latestVersionUrl = `https://github.com/phanan/koel/releases/tag/${commonStore.state.latestVersion}`
const shouldDisplayVersionUpdate = userStore.state.current.is_admin

const hasNewVersion = compareVersions.compare(commonStore.state.latestVersion, commonStore.state.currentVersion, '>')

const emit = defineEmits(['close'])
const close = () => emit('close')
</script>

<style lang="scss" scoped>
.about {
  text-align: center;
  background: var(--color-bg-primary);
  max-width: 480px;
  width: 90%;
  border-radius: .6rem;
  overflow: hidden;

  main {
    padding: 2rem;

    p {
      margin: 1rem 0;
    }
  }

  header, footer {
    padding: 1rem;
    background: rgba(255, 255, 255, .05);
  }

  header {
    font-size: 1.2rem;
    border-bottom: 1px solid rgba(255, 255, 255, .1);
    box-shadow: 0 1px 0 rgba(0, 0, 0, 0.1);
  }

  a {
    color: var(--color-text-primary);

    &:hover {
      color: var(--color-highlight);
    }
  }
}
</style>
