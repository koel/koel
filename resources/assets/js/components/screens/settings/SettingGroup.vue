<template>
  <article class="flex flex-col gap-4">
    <header v-if="$slots.title || $slots.subtitle">
      <h3 v-if="$slots.title" class="text-lg font-semibold text-k-fg">
        <slot name="title" />
      </h3>
      <p v-if="$slots.subtitle" class="text-k-fg-70">
        <slot name="subtitle" />
      </p>
    </header>

    <div>
      <slot />
    </div>

    <footer v-if="$slots.footer">
      <slot name="footer" />
    </footer>
  </article>
</template>
