<template>
  <div class="relative bg-k-bg-input text-k-fg-input rounded border border-k-fg-10">
    <select
      ref="el"
      v-model="value"
      class="appearance-none w-full pl-3.5 pr-8 py-2 text-base text-current"
    >
      <slot />
    </select>
    <Icon
      :icon="faCaretDown"
      class="text-k-highlight pointer-events-none absolute top-1/3 right-[8px]"
      size="sm"
    />
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { faCaretDown } from '@fortawesome/free-solid-svg-icons'

const value = defineModel<any>()

const el = ref<HTMLInputElement>()

defineExpose({
  el,
})
</script>

<style lang="postcss" scoped>
select {
  background: none; /* remove background AND the dropdown arrow. Tailwind doesn't have a way to do this. */
}
</style>
