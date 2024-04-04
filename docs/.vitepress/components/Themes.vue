<template>
  <div class="flex relative">
    <img
      v-for="theme in themes"
      :key="theme.name"
      :alt="`Theme - ${theme.name}`"
      :src="theme.src"
      class="theme"
    />
  </div>
</template>

<script lang="ts" setup>
import themeClassic from '../../assets/img/theme-classic.webp'
import themeDawn from '../../assets/img/theme-dawn.webp'
import themeJungle from '../../assets/img/theme-jungle.webp'
import themeRose from '../../assets/img/theme-rose.webp'

interface Theme {
  src: string
  name: string
}

const themes: Theme[] = [
  { src: themeClassic, name: 'Classic' },
  { src: themeDawn, name: 'Dawn' },
  { src: themeJungle, name: 'Jungle' },
  { src: themeRose, name: 'Rose' },
]
</script>


<style lang="postcss" scoped>
div .theme + .theme {
  margin-left: -100%;
}

.theme {
  transition: clip-path .3s ease-in;
  will-change: clip-path;

  &:hover {
    clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%) !important;
  }

  &:hover ~ & {
    clip-path: polygon(100% 0, 100% 0, 100% 100%, 100% 100%) !important;
  }

  &:has(~ &:hover) {
    clip-path: polygon(0 0, 0 0, 0 100%, 0 100%) !important;
  }
}

.theme:nth-child(4) {
  clip-path: polygon(50% 0, 100% 0, 100% 100%, 25% 100%);
}

.theme:nth-child(3) {
  clip-path: polygon(calc(100% / 3) 0, calc(50% + 1px) 0, calc(25% + 1px) 100%, calc(100% / 12) 100%);
}

.theme:nth-child(2) {
  clip-path: polygon(calc(100% / 6) 0, calc(100% / 3 + 1px) 0, calc(100% / 12 + 1px) 100%, calc(100% / -12) 100%);
}

.theme:nth-child(1) {
  clip-path: polygon(0 0, calc(100% / 6 + 1px) 0, calc(100% / -12 + 1px) 100%, -25% 100%);
}
</style>
