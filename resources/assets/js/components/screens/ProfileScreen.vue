<template>
  <section id="profileWrapper">
    <ScreenHeader>Profile &amp; Preferences</ScreenHeader>

    <div class="main-scroll-wrap">
      <ProfileForm />
      <ThemeList />
      <PreferencesForm />
      <SpotifyIntegration />
      <LastfmIntegration />
    </div>
  </section>
</template>

<script lang="ts" setup>
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ProfileForm from '@/components/profile-preferences/ProfileForm.vue'
import SpotifyIntegration from '@/components/profile-preferences/SpotifyIntegration.vue'
import LastfmIntegration from '@/components/profile-preferences/LastfmIntegration.vue'
import PreferencesForm from '@/components/profile-preferences/PreferencesForm.vue'
import ThemeList from '@/components/profile-preferences/ThemeList.vue'
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
