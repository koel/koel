<template>
  <div class="tabs">
    <header>
      <slot name="header" />
    </header>
    <main>
      <slot />
    </main>
  </div>
</template>

<style lang="postcss" scoped>
:deep(.tabs) {
  @apply flex flex-col overflow-hidden flex-1 text-k-text-secondary;
}

:deep(header) {
  @apply flex bg-white/5 overflow-hidden flex-shrink-0 border-b-white/5;

  label {
    @apply text-base relative uppercase tracking-wider opacity-50 cursor-pointer;
    @apply transition-opacity duration-200 ease-in-out px-7 py-4 rounded-none;

    &:hover {
      @apply opacity-80;
    }

    &.active {
      @apply opacity-100;
    }

    input {
      @apply hidden;
    }
  }
}

:deep(:is(main, .songs-pane, .albums-pane)) {
  @apply flex flex-col flex-1 overflow-auto;
}

:deep(.albums-pane) {
  > ul {
    @apply p-7 overflow-auto;
  }

  .none {
    @apply px-7 py-4;
  }
}

:deep(.info-pane) {
  @apply p-7;
}
</style>
