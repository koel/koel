<template>
  <article
    :style="{ width: `${width}px` }"
    class="skeleton pulse flex rounded-md overflow-hidden h-[1.2rem] m-1 p-4 flex-shrink-0"
  >
    <span class="flex-1" />
    <span class="aspect-[1.2/1] rounded-full pulse" />
  </article>
</template>

<script lang="ts" setup>
const width = Math.random() * 100 + 80
</script>
