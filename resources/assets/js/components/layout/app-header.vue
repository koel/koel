<template>
  <header id="mainHeader">
    <h1 class="brand" v-once>{{ appConfig.name }}</h1>
    <span class="hamburger" @click="toggleSidebar" role="button" title="Show or hide the sidebar">
      <i class="fa fa-bars"></i>
    </span>
    <span class="magnifier" @click="toggleSearchForm" role="button" title="Show or hide the search form">
      <i class="fa fa-search"></i>
    </span>
    <search-form/>
    <div class="header-right">
      <user-badge/>
      <button
        type="button"
        @click.prevent="showAboutDialog"
        class="about control"
        title="About Koel"
        data-testid="about-btn"
      >
        <span v-if="shouldDisplayVersionUpdate && hasNewVersion" class="new-version" data-test="new-version-available">
          {{ sharedState.latestVersion }} available!
        </span>
        <i v-else class="fa fa-info-circle"></i>
      </button>
    </div>
  </header>

</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, reactive } from 'vue'
import compareVersions from 'compare-versions'
import { eventBus } from '@/utils'
import { app as appConfig } from '@/config'
import { sharedStore, userStore } from '@/stores'

const SearchForm = defineAsyncComponent(() => import('@/components/ui/search-form.vue'))
const UserBadge = defineAsyncComponent(() => import('@/components/user/badge.vue'))

const userState = reactive(userStore.state)
const sharedState = reactive(sharedStore.state)

const shouldDisplayVersionUpdate = computed(() => userState.current.is_admin)

const hasNewVersion = computed(() => {
  return compareVersions.compare(sharedState.latestVersion, sharedState.currentVersion, '>')
})

const toggleSidebar = () => eventBus.emit('TOGGLE_SIDEBAR')
const toggleSearchForm = () => eventBus.emit('TOGGLE_SEARCH_FORM')
const showAboutDialog = () => eventBus.emit('MODAL_SHOW_ABOUT_DIALOG')
</script>

<style lang="scss">
#mainHeader {
  height: var(--header-height);
  background: var(--color-bg-secondary);
  display: flex;
  -webkit-app-region: drag;
  box-shadow: 0 0 2px 0 rgba(0, 0, 0, .4);

  input, a {
    -webkit-app-region: no-drag;
  }

  h1.brand {
    flex: 1;
    font-size: 1.7rem;
    font-weight: var(--font-weight-thin);
    opacity: 0;
    line-height: var(--header-height);
    text-align: center;
  }

  .hamburger, .magnifier {
    font-size: 1.4rem;
    flex: 0 0 48px;
    order: -1;
    line-height: var(--header-height);
    text-align: center;
    display: none;
  }

  .header-right {
    display: flex;
    align-items: center;
    flex: 1;
    justify-content: flex-end;

    .about {
      height: 100%;
      @include vertical-center();
      padding: 16px;
      border-left: 1px solid rgba(255, 255, 255, .1);
    }
  }

  @media only screen and (max-width: 667px) {
    display: flex;
    align-content: stretch;
    justify-content: flext-start;

    .hamburger, .magnifier {
      display: inline-block;
    }

    h1.brand {
      opacity: 1;
    }
  }
}
</style>
