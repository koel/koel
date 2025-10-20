<template>
  <button
    class="relative flex items-center justify-center h-[42px] aspect-square rounded-full
    bg-none md:bg-k-fg-10 text-k-fg-70 opacity-50 text-xl transition-opacity duration-200 ease-in-out cursor-pointer
    hover:active-state active:scale-90"
    type="button"
  >
    <slot />
  </button>
</template>

<style lang="postcss" scoped>
@tailwind utilities;

@layer utilities {
  .active-state {
    @apply opacity-100 text-k-fg;
  }
}

button.active {
  @apply active-state;
}
</style>
