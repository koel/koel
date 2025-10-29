<template>
  <div
    :class="{ active: isActive }"
    class="lyrics-line leading-relaxed transition-all duration-300"
  >
    {{ line.text }}
  </div>
</template>

<script lang="ts" setup>
interface LyricLine {
  time: number
  text: string
}

interface Props {
  line: LyricLine
  isActive: boolean
}

defineProps<Props>()
</script>

<style lang="postcss" scoped>
.lyrics-line {
  padding: 0.5rem 1rem;
  opacity: 0.5;
  text-align: center;
  transition: all 0.3s ease;

  &.active {
    opacity: 1;
    font-weight: 600;
    transform: scale(1.05);
    color: var(--color-highlight);
  }
}
</style>
