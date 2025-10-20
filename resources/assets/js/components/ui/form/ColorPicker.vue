<template>
  <label class="inline-flex flex-col gap-2 w-9 items-center relative text-k-fg-70 hover:text-k-fg">
    <input ref="el" v-model="value" :aria-label="ariaLabel" class="absolute w-0 h-0 bottom-0 left-0" type="color">
    <slot />
    <span :style="{ backgroundColor: value }" class="h-2 w-full border border-k-fg-20 pointer-events-none" />
  </label>
</template>

<script setup lang="ts">
import { computed, ref, useAttrs } from 'vue'

const value = defineModel<string>()
const attrs = useAttrs()

const el = ref<HTMLInputElement>()

const ariaLabel = computed(() => String(attrs.ariaLabel || attrs.title || attrs.name))

defineExpose({
  el,
})
</script>
