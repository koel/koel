<template>
  <div class="flex items-center gap-2">
    <button
      v-for="action in actions"
      :key="action.label"
      class="font-semibold underline underline-offset-2 cursor-pointer opacity-80 hover:opacity-100"
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
