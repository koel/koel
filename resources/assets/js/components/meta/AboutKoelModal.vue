<template>
  <div v-koel-focus class="about text-secondary" data-testid="about-modal" tabindex="0" @keydown.esc="close">
    <header>
      <h1 class="text-white">About Koel</h1>
    </header>

    <main>
      <div class="logo">
        <img alt="Koel's logo" src="@/../img/logo.svg" width="128">
      </div>

      <p class="current-version">{{ currentVersion }}</p>

      <p v-if="shouldNotifyNewVersion" class="new-version">
        <a :href="latestVersionReleaseUrl" target="_blank">
          A new Koel version is available ({{ latestVersion }}).
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
import { defineAsyncComponent } from 'vue'
import { isDemo } from '@/utils'
import { useNewVersionNotification } from '@/composables'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))

const {
  shouldNotifyNewVersion,
  currentVersion,
  latestVersion,
  latestVersionReleaseUrl
} = useNewVersionNotification()

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
