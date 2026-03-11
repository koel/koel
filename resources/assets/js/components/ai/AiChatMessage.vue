<template>
  <div class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
    <div
      class="rounded-3xl px-5 py-3 text-lg ai-message"
      :class="message.role === 'user' ? 'user' : message.error ? 'error' : 'assistant'"
      v-html="renderedHtml"
    />
  </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { simpleMarkdownToHtml } from '@/utils/formatters'

const props = defineProps<{
  message: AiChatMessage
}>()

const renderedHtml = computed(() => simpleMarkdownToHtml(props.message.content))
</script>

<style lang="postcss" scoped>
.user {
  @apply bg-white/5 text-k-fg;
}

.assistant {
  @apply text-k-fg;
}

.error {
  @apply bg-red-500/10 text-red-400;
}
</style>
