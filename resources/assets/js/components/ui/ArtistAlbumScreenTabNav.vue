<template>
  <nav>
    <ul class="flex flex-nowrap bg-k-fg-5 overflow-x-auto overflow-y-hidden shrink-0 border-b border-b-k-fg-5">
      <slot />
    </ul>
  </nav>
</template>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
:deep(li) {
  @apply text-base relative uppercase tracking-wider opacity-50 cursor-pointer;
  @apply transition-opacity duration-200 ease-in-out rounded-none;

  &:hover {
    @apply opacity-80;
  }

  &.active,
  &:hover {
    @apply opacity-100;
  }

  a {
    @apply text-k-fg relative px-7 py-4 inline-block;
  }
}
</style>
