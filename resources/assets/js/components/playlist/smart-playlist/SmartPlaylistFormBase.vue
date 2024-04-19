<template>
  <div class="smart-playlist-form md:w-[560px]">
    <slot />
  </div>
</template>

<script lang="ts" setup>
</script>

<style lang="postcss" scoped>
.smart-playlist-form {
  max-height: calc(100vh - 4rem);
}

:slotted(form) {
  max-height: calc(100vh - 4rem);
  @apply flex flex-col overflow-auto;
}
</style>
