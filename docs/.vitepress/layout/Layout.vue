<template>
  <BaseLayout>
    <template #aside-outline-after>
      <nav class="aside-outline-after">
      <Sponsors />
      </nav>
    </template>
  </BaseLayout>
</template>

<script setup>
import DefaultTheme from 'vitepress/theme'
import Sponsors from '../components/Sponsors.vue'

const { Layout: BaseLayout } = DefaultTheme
</script>

<style lang="scss">
.aside-outline-after {
  margin-top: 1.5rem;
}
</style>
