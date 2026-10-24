<template>
  <article class="flex gap-3 py-3 pl-4 pr-3 rounded-xl border border-k-fg-10 bg-k-bg-context-menu text-k-fg shadow-lg">
    <span class="shrink-0 flex">
      <slot name="icon" />
    </span>
    <slot />
    <button v-if="dismissible" type="button" title="Dismiss" class="shrink-0 mt-0.5" @click="emit('dismiss')">
      <XIcon :size="16" />
    </button>
  </article>
</template>

<script lang="ts" setup>
import { XIcon } from 'lucide-vue-next'

withDefaults(defineProps<{ dismissible?: boolean }>(), { dismissible: false })

const emit = defineEmits<{ (e: 'dismiss'): void }>()
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

button {
  @apply text-k-fg-70 hover:text-k-fg;
}
</style>
