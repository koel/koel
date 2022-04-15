<template>
  <section id="profileWrapper">
    <ScreenHeader>Profile &amp; Preferences</ScreenHeader>

    <div class="main-scroll-wrap">
      <ProfileForm/>
      <Themes/>
      <Preferences/>
      <LastfmIntegration/>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { defineAsyncComponent } from 'vue'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/screen-header.vue'))
const ProfileForm = defineAsyncComponent(() => import('@/components/profile-preferences/profile-form.vue'))
const LastfmIntegration = defineAsyncComponent(() => import('@/components/profile-preferences/lastfm-integration.vue'))
const Preferences = defineAsyncComponent(() => import('@/components/profile-preferences/preferences.vue'))
const Themes = defineAsyncComponent(() => import('@/components/profile-preferences/themes.vue'))
</script>

<style lang="scss">
#profileWrapper {
  .main-scroll-wrap > * + * {
    margin-top: 1.75rem;
    padding-top: 1.75rem;
    border-top: 1px solid var(--color-background-secondary);
  }

  .main-scroll-wrap h1 {
    font-size: 1.85rem;
    margin-bottom: 1.25rem;
  }
}
</style>
