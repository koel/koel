<template>
  <div class="tabs">
    <main>
      <slot />
    </main>
  </div>
</template>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
:deep(.tabs) {
  @apply flex flex-col overflow-hidden flex-1;
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
