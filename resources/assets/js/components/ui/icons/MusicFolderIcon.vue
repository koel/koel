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
    <path
      d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"
    />
    <circle cx="10" cy="16" r="2" />
    <path d="M12 16v-6l3 1" />
  </svg>
</template>

<script lang="ts" setup>
withDefaults(defineProps<{ size?: number }>(), { size: 24 })
</script>
