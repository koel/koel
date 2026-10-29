<template>
  <div class="flex items-center gap-2">
    <button
      v-for="action in actions"
      :key="action.label"
      class="px-2 py-0.5 rounded-sm border border-current/50 cursor-pointer hover:border-current"
      type="button"
      @click="action.handler()"
    >
      <template v-if="action.icon">
        <component :is="action.icon" :size="14" />
        <span class="sr-only">{{ action.label }}</span>
      </template>
      <template v-else>{{ action.label }}</template>
    </button>
  </div>
</template>

<script lang="ts" setup>
defineProps<{ actions: ToastAction[] }>()
</script>
