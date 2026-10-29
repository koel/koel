<template>
  <div class="flex items-center gap-2">
    <button
      v-for="action in actions"
      :key="action.label"
      class="cursor-pointer hover:opacity-70"
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
