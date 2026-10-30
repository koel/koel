<template>
  <div class="flex items-center gap-1">
    <Btn
      v-for="action in actions"
      :key="action.label"
      :variant="action.primary ? 'highlight' : 'ghost'"
      size="small"
      type="button"
      @click="action.handler()"
    >
      {{ action.label }}
    </Btn>
  </div>
</template>

<script lang="ts" setup>
import Btn from '@/components/ui/form/Btn.vue'

defineProps<{ actions: ToastAction[] }>()
</script>
