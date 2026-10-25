<template>
  <span class="flex">
    <slot />
  </span>
</template>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

:slotted(button) {
  @apply text-k-fg bg-k-bg border border-r-0 border-solid border-white/20 opacity-80
  px-3 py-1.5 hover:opacity-100 active:scale-95;
}

:slotted(button:first-child) {
  @apply rounded-l;
}

:slotted(button:last-child) {
  @apply rounded-r border-r;
}
</style>
