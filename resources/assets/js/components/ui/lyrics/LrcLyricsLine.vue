<template>
  <p
    :class="{ active: isActive }"
    class="relative leading-relaxed transition-all duration-200 cursor-default text-k-fg"
  >
    {{ line.text }}
  </p>
</template>

<script lang="ts" setup>
defineProps<{
  line: LrcLine
  isActive: boolean
}>()
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
p {
  &.active {
    @apply text-[1.05em] font-semibold;
  }
}
</style>
