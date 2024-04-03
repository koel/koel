<template>
  <section>
    <ul class="themes">
      <li v-for="theme in themes" :key="theme.id" data-testid="theme-card">
        <ThemeCard :key="theme.id" :theme="theme" @selected="setTheme" />
      </li>
    </ul>
  </section>
</template>

<script lang="ts" setup>
import { toRef } from 'vue'
import { themeStore } from '@/stores'

import ThemeCard from '@/components/profile-preferences/ThemeCard.vue'

const themes = toRef(themeStore.state, 'themes')

const setTheme = (theme: Theme) => themeStore.setTheme(theme)
</script>

<style lang="scss" scoped>
.themes {
  display: grid;
  grid-auto-rows: 8rem;
  grid-template-columns: auto auto auto;
  grid-gap: .75rem 1rem;

  @media only screen and (max-width: 768px) {
    grid-template-columns: auto auto;
  }

  @media only screen and (max-width: 480px) {
    grid-template-columns: auto;
  }
}
</style>
