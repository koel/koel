<template>
  <a :href="sponsor.url" :title="`${sponsor.name} – ${sponsor.slogan}`">
    <img :alt="`${sponsor.name} logo`" :src="sponsor.logo" v-bind="$attrs">
  </a>
</template>

<script setup lang="ts">
interface Sponsor {
  name: string
  url: string
  logo: string
  slogan: string
}

const sponsor = defineProps<Sponsor>()

defineOptions({
  inheritAttrs: false
})
</script>

<style scoped lang="postcss">
img {
  opacity: 0.8;

  &:hover {
    opacity: 1;
  }
}
.dark img {
  filter: brightness(0) invert(1);
}
</style>
