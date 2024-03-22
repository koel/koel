<template>
  <div v-koel-focus class="about text-secondary" data-testid="about-koel" tabindex="0" @keydown.esc="close">
    <main>
      <div class="logo">
        <img alt="Koel's logo" src="@/../img/logo.svg" width="128">
      </div>

      <div class="current-version">
        Koel {{ currentVersion }}
        <span v-if="isPlus" class="badge">Plus</span>
        <span v-else>Community</span>
        Edition
        <p v-if="isPlus" class="plus-badge">
          Licensed to {{ license.customerName }} &lt;{{ license.customerEmail }}&gt;
          <br>
          License key: <span class="key">{{ license.shortKey }}</span>
        </p>

        <template v-else>
          <p v-if="isAdmin" class="upgrade">
            <!-- close the modal first to prevent it from overlapping Lemonsqueezy's overlay -->
            <BtnUpgradeToPlus @click.prevent="showPlusModal" />
          </p>
        </template>
      </div>

      <p v-if="shouldNotifyNewVersion" data-testid="new-version-about">
        <a :href="latestVersionReleaseUrl" target="_blank">
          A new version of Koel is available ({{ latestVersion }})!
        </a>
      </p>

      <p class="author">
        Made with ❤️ by
        <a href="https://github.com/phanan" rel="noopener" target="_blank">Phan An</a>
        and quite a few
        <a href="https://github.com/koel/core/graphs/contributors" rel="noopener" target="_blank">awesome</a>&nbsp;<a
          href="https://github.com/koel/koel/graphs/contributors" rel="noopener" target="_blank"
        >contributors</a>.
      </p>

      <div v-if="credits" class="credit-wrapper" data-testid="demo-credits">
        Music by
        <ul class="credits">
          <li v-for="credit in credits" :key="credit.name">
            <a :href="credit.url" target="_blank">{{ credit.name }}</a>
          </li>
        </ul>
      </div>

      <SponsorList />

      <p v-if="!isPlus">
        Loving Koel? Please consider supporting its development via
        <a href="https://github.com/users/phanan/sponsorship" rel="noopener" target="_blank">GitHub Sponsors</a>
        and/or
        <a href="https://opencollective.com/koel" rel="noopener" target="_blank">OpenCollective</a>.
      </p>
    </main>

    <footer>
      <Btn data-testid="close-modal-btn" red rounded @click.prevent="close">Close</Btn>
    </footer>
  </div>
</template>

<script lang="ts" setup>
import { orderBy } from 'lodash'
import { onMounted, ref } from 'vue'
import { useAuthorization, useKoelPlus, useNewVersionNotification } from '@/composables'
import { http } from '@/services'
import { eventBus } from '@/utils'

import SponsorList from '@/components/meta/SponsorList.vue'
import Btn from '@/components/ui/Btn.vue'
import BtnUpgradeToPlus from '@/components/koel-plus/BtnUpgradeToPlus.vue'

type DemoCredits = {
  name: string
  url: string
}

const credits = ref<DemoCredits[] | null>(null)

const {
  shouldNotifyNewVersion,
  currentVersion,
  latestVersion,
  latestVersionReleaseUrl
} = useNewVersionNotification()

const { isPlus, license } = useKoelPlus()
const { isAdmin } = useAuthorization()

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const showPlusModal = () => {
  close()
  eventBus.emit('MODAL_SHOW_KOEL_PLUS')
}

onMounted(async () => {
  credits.value = window.IS_DEMO ? orderBy(await http.get<DemoCredits[]>('demo/credits'), 'name') : null
})
</script>

<style lang="scss" scoped>
.about {
  text-align: center;
  max-width: 480px;
  overflow: hidden;
  position: relative;

  main {
    padding: 1.8rem;

    p {
      margin: 1rem 0;
    }
  }

  footer {
    padding: 1rem;
    background: rgba(255, 255, 255, .02);
  }

  a {
    color: var(--color-text-primary);

    &:hover {
      color: var(--color-accent);
    }
  }
}

.credit-wrapper {
  max-height: 9rem;
  overflow: auto;
}

.credits, .credits li {
  display: inline;
}

.credits {
  display: inline;

  li {
    display: inline;

    &:last-child {
      &::before {
        content: ', and '
      }

      &::after {
        content: '.';
      }
    }
  }

  li + li {
    &::before {
      content: ', ';
    }
  }
}

.sponsors {
  margin-top: 1rem;
}

.plus-badge {
  .key {
    font-family: monospace;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-image: linear-gradient(97.78deg, #c62be8 17.5%, #671ce4 113.39%);
  }
}

.upgrade {
  padding: .5rem 0;
}
</style>
