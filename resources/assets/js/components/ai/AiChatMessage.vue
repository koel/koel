<template>
  <div class="flex" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
    <div class="relative group">
      <div
        class="rounded-3xl px-5 py-3 text-lg ai-message"
        :class="message.role === 'user' ? 'user' : message.error ? 'error' : 'assistant'"
        v-html="renderedHtml"
      />
      <button
        v-if="message.role === 'assistant' && !message.error"
        class="copy-btn"
        title="Copy to clipboard"
        @click="copy"
      >
        <ClipboardCheckIcon v-if="copied" class="size-4" />
        <CopyIcon v-else class="size-4" />
      </button>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { CopyIcon, ClipboardCheckIcon } from 'lucide-vue-next'
import { copyText } from '@/utils/helpers'
import { simpleMarkdownToHtml } from '@/utils/formatters'

const props = defineProps<{
  message: AiChatMessage
}>()

const renderedHtml = computed(() => simpleMarkdownToHtml(props.message.content))

const copied = ref(false)
let copiedTimeout = 0

const copy = async () => {
  await copyText(props.message.content)
  copied.value = true
  clearTimeout(copiedTimeout)
  copiedTimeout = window.setTimeout(() => (copied.value = false), 2000)
}
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

.copy-btn {
  @apply absolute -bottom-6 left-4 opacity-0 group-hover:opacity-100 transition-opacity
    text-k-fg-50 hover:text-k-fg p-1 rounded;
}
</style>
