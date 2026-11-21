<template>
  <div class="pt-6">
    <header class="flex items-center justify-between gap-3 mb-8">
      <h3 class="text-2xl font-thin text-k-fg min-w-0">
        <slot name="header" />
      </h3>
      <div class="flex items-center gap-2 shrink-0">
        <slot name="actions" />
      </div>
    </header>

    <slot />
  </div>
</template>
