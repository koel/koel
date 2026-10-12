<template>
  <article :class="layout" class="skeleton flex flex-col overflow-hidden rounded-xl border border-k-fg-10">
    <aside class="aspect-square pulse" />

    <footer class="flex flex-col flex-1 gap-3 p-5">
      <p class="h-[1.2rem] w-3/4 pulse" />
      <p class="h-[1.2rem] w-2/3 pulse" />
      <p class="h-[1.2rem] w-1/3 pulse" />
    </footer>
  </article>
</template>

<script lang="ts" setup>
withDefaults(defineProps<{ layout?: CardLayout }>(), { layout: 'full' })
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.full {
  @apply aspect-[3/4];
}

.compact {
  @apply flex-row items-stretch max-w-full min-h-24 rounded-md;

  aside {
    @apply w-28 aspect-auto;
  }

  footer {
    @apply justify-center p-3;
  }
}
</style>
