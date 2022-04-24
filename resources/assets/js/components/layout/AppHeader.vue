<template>
  <header id="mainHeader">
    <h1 class="brand" v-once>{{ appConfig.name }}</h1>
    <span class="hamburger" role="button" title="Show or hide the sidebar" @click="toggleSidebar">
      <i class="fa fa-bars"></i>
    </span>
    <span class="magnifier" role="button" title="Show or hide the search form" @click="toggleSearchForm">
      <i class="fa fa-search"></i>
    </span>
    <search-form/>
    <div class="header-right">
      <user-badge/>
      <button
        class="about control"
        data-testid="about-btn"
        title="About Koel"
        type="button"
        @click.prevent="showAboutDialog"
      >
        <span v-if="shouldDisplayVersionUpdate && hasNewVersion" class="new-version" data-test="new-version-available">
          {{ state.latestVersion }} available!
        </span>
        <i v-else class="fa fa-info-circle"></i>
      </button>
    </div>
  </header>

</template>

<script lang="ts" setup>
import compareVersions from 'compare-versions'
import { computed, defineAsyncComponent, toRef } from 'vue'
import { eventBus } from '@/utils'
import { app as appConfig } from '@/config'
import { commonStore, userStore } from '@/stores'

const SearchForm = defineAsyncComponent(() => import('@/components/ui/SearchForm.vue'))
const UserBadge = defineAsyncComponent(() => import('@/components/user/UserBadge.vue'))

const user = toRef(userStore.state, 'current')
const state = commonStore.state

const shouldDisplayVersionUpdate = computed(() => user.value.is_admin)
const hasNewVersion = computed(() => compareVersions.compare(state.latestVersion, state.currentVersion, '>'))

const toggleSidebar = () => eventBus.emit('TOGGLE_SIDEBAR')
const toggleSearchForm = () => eventBus.emit('TOGGLE_SEARCH_FORM')
const showAboutDialog = () => eventBus.emit('MODAL_SHOW_ABOUT_KOEL')
</script>

<style lang="scss">
#mainHeader {
  height: var(--header-height);
  background: var(--color-bg-secondary);
  display: flex;
  box-shadow: 0 0 2px 0 rgba(0, 0, 0, .4);

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
