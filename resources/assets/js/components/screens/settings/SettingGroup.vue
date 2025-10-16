<template>
  <article class="flex flex-col md:flex-row gap-4">
    <header class="w-full md:w-64">
      <h3 class="font-semibold text-k-fg">
        <slot name="title" />
      </h3>
      <p v-if="$slots.subtitle">
        <slot name="subtitle" />
      </p>
    </header>
    <main class="flex-1 bg-k-fg-5 border border-k-fg-10 rounded-md">
      <div class="p-4">
        <slot />
      </div>

      <footer class="p-4 border-t border-k-fg-10">
        <slot name="footer" />
      </footer>
    </main>
  </article>
</template>
