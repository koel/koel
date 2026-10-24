<template>
  <div class="fixed z-10000 left-4 flex flex-col items-start gap-3">
    <slot />
  </div>
</template>

<style lang="postcss" scoped>
div {
  bottom: calc(var(--footer-height) + 2rem);
}
</style>
