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

:deep(:is(main, .songs-pane)) {
  @apply flex flex-col flex-1 overflow-auto;
}
</style>
