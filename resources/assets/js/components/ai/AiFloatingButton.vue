<template>
  <button
    class="ai-float-btn group w-12 h-12 rounded-full cursor-pointer p-0.5"
    title="AI Assistant"
    type="button"
    @click="go(url('ai'))"
  >
    <span class="w-full h-full rounded-full bg-[#1a1a2e] flex items-center justify-center">
      <Sparkles class="w-5 h-5 text-k-fg group-hover:text-k-fg transition-colors" />
    </span>
  </button>
</template>

<script lang="ts" setup>
import { Sparkles } from 'lucide-vue-next'
import { useRouter } from '@/composables/useRouter'

const { go, url } = useRouter()
</script>

<style scoped>
@property --angle {
  syntax: '<angle>';
  initial-value: 0deg;
  inherits: false;
}

.ai-float-btn {
  --angle: 0deg;
  background: conic-gradient(from var(--angle), #f06, #f90, #ff0, #3c0, #09f, #93f, #f06);
}

.ai-float-btn:hover {
  animation: spin-rainbow 2s linear infinite;
}

@keyframes spin-rainbow {
  to {
    --angle: 360deg;
  }
}
</style>
