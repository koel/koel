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
  animate: boolean
}>()

const bubbleClasses = computed(() => {
  if (props.message.role === 'user') {
    return 'bg-k-highlight/20 text-k-fg'
  }

  return props.message.error ? 'bg-red-500/10 text-red-400' : 'bg-white/5 text-k-fg'
})

const renderedHtml = computed(() => {
  const html = simpleMarkdownToHtml(props.message.content)

  if (!props.animate || props.message.role === 'user') {
    return html
  }

  let wordIndex = 0

  return html.replace(/(<[^>]+>)|(\S+)/g, (match, tag) => {
    if (tag) {
      return match
    }

    const delay = wordIndex * 60
    wordIndex++
    return `<span class="ai-word" style="animation-delay:${delay}ms">${match}</span>`
  })
})
</script>

<style lang="postcss" scoped>
.ai-message :deep(.ai-word) {
  opacity: 0;
  animation: word-fade-in 0.4s ease forwards;
}

@keyframes word-fade-in {
  from {
    opacity: 0;
  }

  to {
    opacity: 1;
  }
}
</style>
