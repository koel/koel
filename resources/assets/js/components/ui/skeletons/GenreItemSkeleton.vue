<template>
  <article class="skeleton pulse" :style="{ width: `${width}px` }">
    <span class="name" />
    <span class="count pulse" />
  </article>
</template>

<script lang="ts" setup>
const width = Math.random() * 100 + 80
</script>

<style lang="scss" scoped>
.skeleton {
  display: flex;
  border-radius: 999rem;
  overflow: hidden;
  height: 28px;
  margin: .4rem;
  padding: 4px;
  flex-shrink: 0;

  .name {
    flex: 1;
  }

  .count {
    aspect-ratio: 1.2/1;
    border-radius: 9999rem;
  }
}
</style>
