<template>
  <button v-koel-tooltip class="relative transition-[color] duration-200 ease-in-out hover:text-k-fg" type="button">
    <slot />
  </button>
</template>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
button::after {
  content: '';
  @apply absolute -inset-3;
}
</style>
