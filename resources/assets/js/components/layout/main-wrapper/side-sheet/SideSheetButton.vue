<template>
  <button
    class="relative flex items-center justify-center h-[42px] aspect-square rounded-full bg-none md:bg-k-fg-10 text-k-fg-70 opacity-50 text-xl transition-opacity duration-200 ease-in-out cursor-pointer hover:opacity-100 hover:text-k-fg active:scale-90"
    type="button"
  >
    <slot />
  </button>
</template>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

button.active {
  @apply opacity-100 text-k-fg;
}
</style>
