<template>
  <div class="flex items-center gap-2">
    <button
      v-for="action in actions"
      :key="action.label"
      class="px-2 py-0.5 rounded-sm border border-current/50 cursor-pointer hover:border-current"
      type="button"
      @click="action.handler()"
    >
      {{ action.label }}
    </button>
  </div>
</template>

<script lang="ts" setup>
defineProps<{ actions: ToastAction[] }>()
</script>
