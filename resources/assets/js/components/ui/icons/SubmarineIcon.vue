<template>
  <svg
    :height="size"
    :width="size"
    fill="none"
    stroke="currentColor"
    stroke-linecap="round"
    stroke-linejoin="round"
    stroke-width="2"
    viewBox="0 0 24 24"
    xmlns="http://www.w3.org/2000/svg"
  >
    <rect height="8" rx="4" width="18" x="2" y="11" />
    <path d="M8 11V8a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3" />
    <path d="M11 7V4h2" />
    <path d="M20 15h2" />
    <path d="M22 12v6" />
    <path d="M7 15h.01" />
    <path d="M11 15h.01" />
    <path d="M15 15h.01" />
  </svg>
</template>

<script lang="ts" setup>
withDefaults(defineProps<{ size?: number }>(), { size: 24 })
</script>
