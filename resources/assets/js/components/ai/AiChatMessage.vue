<template>
  <div class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
    <div class="max-w-[80%] rounded-2xl px-4 py-3 text-base ai-message" :class="bubbleClasses" v-html="renderedHtml" />
  </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { simpleMarkdownToHtml } from '@/utils/formatters'

const props = defineProps<{
  message: AiChatMessage
}>()

const bubbleClasses = computed(() => {
  if (props.message.role === 'user') {
    return 'bg-k-highlight/20 text-k-fg'
  }

  return props.message.error ? 'bg-red-500/10 text-red-400' : 'bg-white/5 text-k-fg'
})

const renderedHtml = computed(() => simpleMarkdownToHtml(props.message.content))
</script>
