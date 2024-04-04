<template>
  <section>
    <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
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
