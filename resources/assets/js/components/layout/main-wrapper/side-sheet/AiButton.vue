<template>
  <button
    v-koel-tooltip.left
    class="ai-btn relative flex items-center justify-center h-[42px] aspect-square rounded-full cursor-pointer p-0.5 active:scale-90"
    title="AI Assistant (/)"
    type="button"
    @click.prevent="go(url('ai'))"
  >
    <span class="w-full h-full rounded-full bg-[#1a1a2e] flex items-center justify-center">
      <svg
        class="size-5"
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <defs>
          <linearGradient id="ai-btn-gradient" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="24" y2="24">
            <stop offset="0%" stop-color="#22d3ee" />
            <stop offset="100%" stop-color="#c084fc" />
          </linearGradient>
        </defs>
        <path
          stroke="url(#ai-btn-gradient)"
          d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"
        />
        <path stroke="url(#ai-btn-gradient)" d="M20 3v4" />
        <path stroke="url(#ai-btn-gradient)" d="M22 5h-4" />
        <path stroke="url(#ai-btn-gradient)" d="M4 17v2" />
        <path stroke="url(#ai-btn-gradient)" d="M5 18H3" />
      </svg>
    </span>
  </button>
</template>

<script lang="ts" setup>
import { useRouter } from '@/composables/useRouter'

const { go, url } = useRouter()
</script>

<style lang="postcss" scoped>
@property --angle {
  syntax: '<angle>';
  initial-value: 0deg;
  inherits: false;
}

.ai-btn {
  --angle: 0deg;
  background: conic-gradient(from var(--angle), #f06, #f90, #ff0, #3c0, #09f, #93f, #f06);
}

.ai-btn:hover {
  animation: spin-rainbow 2s linear infinite;
}

@keyframes spin-rainbow {
  to {
    --angle: 360deg;
  }
}
</style>
