<template>
  <button
    class="relative flex items-center justify-center h-[42px] aspect-square rounded-full
    bg-none md:bg-black/30 text-xl opacity-70 transition-opacity duration-200 ease-in-out text-current cursor-pointer
    hover:active-state active:scale-90"
    type="button"
  >
    <slot />
  </button>
</template>

<style scoped lang="postcss">
@tailwind utilities;

@layer utilities {
  .active-state {
    @apply opacity-100 text-k-text-primary bg-white/10;
  }
}

button.active {
  @apply active-state;
}
</style>
