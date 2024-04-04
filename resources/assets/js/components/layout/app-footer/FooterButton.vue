<template>
  <button
    v-koel-tooltip.top
    class="transition-[color] duration-200 ease-in-out text-k-text-secondary hover:text-k-text-primary"
    type="button"
  >
    <slot />
  </button>
</template>
