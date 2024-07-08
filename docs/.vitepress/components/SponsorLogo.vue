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
a {
  display: block;
  padding: .5rem .7rem;
  background: rgba(0, 0, 0, .05);
  border-radius: 4px;

  &:hover {
    background: rgba(0, 0, 0, .1);
  }
}

.dark {
  a {
    background: rgba(255, 255, 255, .2);

    img {
      filter: grayscale(1) invert(1);
    }

    &:hover {
      background: #fff;

      img {
        filter: none;
      }
    }
  }
}
</style>
