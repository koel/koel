<template>
  <div class="about text-secondary" tabindex="0" v-koel-focus @keydown.esc="close" data-testid="about-modal">
    <header>
      <h1 class="text-white">About Koel</h1>
    </header>

    <main>
      <div class="logo">
        <img src="@/../img/logo.svg" width="128" height="auto" alt="Koel's logo">
      </div>

      <p class="current-version">{{ sharedState.currentVersion }}</p>

      <p v-if="shouldDisplayVersionUpdate && hasNewVersion" class="new-version">
        <a :href="latestVersionUrl" target="_blank">
            A new Koel version is available ({{ sharedState.latestVersion }}).
        </a>
      </p>

      <p class="author">
        Made with ❤️ by
        <a href="https://github.com/phanan" target="_blank" rel="noopener">Phan An</a>
        and quite a few
        <a href="https://github.com/koel/core/graphs/contributors" target="_blank" rel="noopener">awesome</a>
        <a href="https://github.com/koel/koel/graphs/contributors" target="_blank" rel="noopener">contributors</a>.
      </p>

      <p class="demo-credits" v-if="demo">
        Demo music provided by
        <a href="https://www.bensound.com" target="_blank" rel="noopener">Bensound</a>.
      </p>

      <p>
        Loving Koel? Please consider supporting its development via
        <a href="https://github.com/users/phanan/sponsorship" target="_blank" rel="noopener">GitHub Sponsors</a>
        and/or
        <a href="https://opencollective.com/koel" target="_blank" rel="noopener">OpenCollective</a>.
      </p>
    </main>

    <footer>
      <btn @click.prevent="close" red rounded data-test="close-modal-btn">Close</btn>
    </footer>
  </div>
</template>

<script lang="ts">
import Vue from 'vue'
import compareVersions from 'compare-versions'
import { sharedStore, userStore } from '@/stores'

export default Vue.extend({
  components: {
    Btn: () => import('@/components/ui/btn.vue')
  },

  data: () => ({
    userState: userStore.state,
    sharedState: sharedStore.state,
    demo: NODE_ENV === 'demo'
  }),

  computed: {
    latestVersionUrl (): string {
      return `https://github.com/phanan/koel/releases/tag/${this.sharedState.latestVersion}`
    },

    shouldDisplayVersionUpdate (): boolean {
      return this.userState.current.is_admin
    },

    hasNewVersion (): boolean {
      return compareVersions.compare(this.sharedState.latestVersion, this.sharedState.currentVersion, '>')
    }
  },

  methods: {
    close (): void {
      this.$emit('close')
    }
  }
})
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
