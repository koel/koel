<template>
  <section>
    <h1>Theme</h1>
    <ul class="themes">
      <li v-for="theme in themes" :key="theme.id">
        <theme-card :theme="theme" :key="theme.id" @selected="setTheme"/>
      </li>
    </ul>
  </section>
</template>

<script lang="ts">
import Vue from 'vue'
import { themeStore } from '@/stores'

export default Vue.extend({
  components: {
    ThemeCard: () => import('@/components/profile-preferences/theme-card.vue')
  },

  data: () => ({
    themes: themeStore.state.themes
  }),

  methods: {
    setTheme: (theme: Theme): void => themeStore.setTheme(theme)
  }
})
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
