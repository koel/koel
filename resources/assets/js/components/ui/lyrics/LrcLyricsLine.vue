<template>
  <p
    :class="{ active: isActive }"
    class="relative leading-relaxed origin-left transition-all duration-200 cursor-default text-k-fg"
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
p {
  &.active {
    @apply scale-105 font-semibold;
  }
}
</style>
