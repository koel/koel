<template>
  <article :class="layout" class="skeleton flex flex-col gap-3 p-5 rounded-lg max-w-[256px] border border-k-border">
    <aside class="aspect-square rounded-lg pulse" />

    <footer class="flex flex-col flex-1 gap-3">
      <p class="h-[1.2rem] w-3/4 pulse" />
      <p class="h-[1.2rem] w-2/3 pulse" />
      <p class="h-[1.2rem] w-1/3 pulse" />
    </footer>
  </article>
</template>

<script lang="ts" setup>
const props = withDefaults(defineProps<{ layout?: ArtistAlbumCardLayout }>(), { layout: 'full' })
</script>

<style lang="postcss" scoped>
.compact {
  @apply flex-row items-center max-w-full p-4 rounded-md;

  aside {
    @apply w-[80px];
  }
}
</style>
