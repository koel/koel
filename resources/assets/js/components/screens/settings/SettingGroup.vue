<template>
  <article class="flex">
    <header class="w-64">
      <h3 class="font-semibold">
        <slot name="title" />
      </h3>
      <p v-if="$slots.subtitle" class="text-k-text-secondary">
        <slot name="subtitle" />
      </p>
    </header>
    <main class="flex-1 bg-white/5 border border-white/10 rounded-md">
      <div class="p-4">
        <slot />
      </div>

      <footer class="p-4 border-t border-white/10">
        <slot name="footer" />
      </footer>
    </main>
  </article>
</template>
