<template>
  <article
    class="relative flex place-content-center place-items-center flex-1 text-white/50"
    data-testid="screen-empty-state"
  >
    <div class="md:max-w-3/4 px-6 pt-0 mx-auto flex flex-col items-center pb-k-header-height">
      <span class="jumbo-icon block text-[6rem] mb-4 opacity-70">
        <slot name="icon">☕️</slot>
      </span>
      <div class="text text-center text-3xl font-light flex flex-col gap-2">
        <slot>Placeholder text goes here.</slot>
      </div>
    </div>
  </article>
</template>

<style lang="postcss">
.text {
  a {
    @apply text-white/80;

    &:hover {
      @apply text-white;
    }
  }

  .secondary {
    @apply text-2xl font-thin;
  }
}
</style>
