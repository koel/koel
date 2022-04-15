<template>
  <section>
    <h1>Theme</h1>
    <ul class="themes">
      <li v-for="theme in themes" :key="theme.id">
        <ThemeCard :theme="theme" :key="theme.id" @selected="setTheme"/>
      </li>
    </ul>
  </section>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, reactive } from 'vue'
import { themeStore } from '@/stores'

const ThemeCard = defineAsyncComponent(() => import('@/components/profile-preferences/theme-card.vue'))
const themes = reactive(themeStore.state.themes)

const setTheme = (theme: Theme) => themeStore.setTheme(theme)
</script>

<style lang="scss" scoped>
.themes {
  display: grid;
  grid-auto-rows: 8rem;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  grid-gap: .75rem 1rem;

  @media only screen and (max-width: 667px) {
    grid-template-columns: 1fr;
  }
}
</style>
