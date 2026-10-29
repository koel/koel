<template>
  <div class="flex items-center gap-2">
    <button
      v-for="action in actions"
      :key="action.label"
      class="underline underline-offset-2 cursor-pointer"
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
