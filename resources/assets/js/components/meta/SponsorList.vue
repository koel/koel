<template>
  <div class="sponsors">
    <a
      v-for="sponsor in sponsors"
      :key="sponsor.url"
      :href="sponsor.url"
      :title="sponsor.description"
      target="_blank"
    >
      <img :alt="sponsor.description" :src="sponsor.logo.src" :style="sponsor.logo.style">
    </a>
  </div>
</template>

<script lang="ts" setup>
import sponsors from '@/sponsors'</script>

<style lang="postcss" scoped>
.sponsors {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  justify-content: center;
  row-gap: 4px;
  column-gap: 16px;
  padding: 8px;
  background: rgba(0, 0, 0, .1);
  border-radius: 5px;
  border: 1px solid rgba(255, 255, 255, .05);

  img {
    filter: brightness(10);
    height: 32px;
  }
}
</style>
