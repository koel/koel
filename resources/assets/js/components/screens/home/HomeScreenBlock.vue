<template>
  <div>
    <h3 class="text-2xl mb-8 font-thin text-k-fg">
      <slot name="header" />
    </h3>
<section class="mb-6 rounded-lg border border-k-border bg-k-bg-secondary p-4">
  <h4 class="mb-2 text-base font-semibold text-k-fg">
    Library Statistics
  </h4>

  <p class="text-sm text-k-text-secondary">
    View total songs, albums, artists, and library duration from your music collection.
  </p>

  <p class="mt-2 text-xs text-k-text-secondary">
    Statistics are loaded from the library statistics API.
  </p>
</section>

<slot />
  </div>
</template>
