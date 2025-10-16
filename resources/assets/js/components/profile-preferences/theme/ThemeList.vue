<template>
  <ul class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
    <li v-for="theme in themes" :key="theme.id">
      <ThemeCard :key="theme.id" :theme />
    </li>
  </ul>
</template>

<script setup lang="ts">
import ThemeCard from '@/components/profile-preferences/theme/ThemeCard.vue'

defineProps<{ themes: Theme[] }>()
</script>
