<template>
  <div class="flex items-end gap-2" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
    <div class="relative group">
      <div
        class="rounded-3xl px-5 py-3 text-lg ai-message"
        :class="message.role === 'user' ? 'user' : message.error ? 'error' : 'assistant'"
        v-html="renderedHtml"
      />
      <button
        v-if="message.role === 'assistant' && !message.error"
        class="copy-btn"
        type="button"
        aria-label="Copy message to clipboard"
        title="Copy to clipboard"
        @click="copy"
      >
        <ClipboardCheckIcon v-if="copied" class="size-4" />
        <CopyIcon v-else class="size-4" />
      </button>
    </div>
    <UserAvatar v-if="message.role === 'user'" :user class="size-8 flex-shrink-0" />
  </div>
</template>

<script lang="ts" setup>
import DOMPurify from 'dompurify'
import { marked } from 'marked'
import { computed, onBeforeUnmount, ref } from 'vue'
import { CopyIcon, ClipboardCheckIcon } from 'lucide-vue-next'
import { copyText } from '@/utils/helpers'

import UserAvatar from '@/components/user/UserAvatar.vue'

const props = defineProps<{
  message: AiChatMessage
  user: Pick<User, 'name' | 'avatar'>
}>()

const renderedHtml = computed(() => DOMPurify.sanitize(marked.parse(props.message.content) as string))

const copied = ref(false)
let copiedTimeout = 0

const copy = async () => {
  await copyText(props.message.content)
  copied.value = true
  clearTimeout(copiedTimeout)
  copiedTimeout = window.setTimeout(() => (copied.value = false), 2000)
}

onBeforeUnmount(() => {
  clearTimeout(copiedTimeout)
})
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
  @apply absolute -bottom-6 left-4 opacity-0 group-hover:opacity-100 focus:opacity-100 transition-opacity
    text-k-fg-50 hover:text-k-fg p-1 rounded;
}

.ai-message {
  :deep(p + p) {
    @apply mt-2;
  }

  :deep(ul),
  :deep(ol) {
    @apply ml-5 my-2;
  }

  :deep(ul) {
    @apply list-disc;
  }

  :deep(ol) {
    @apply list-decimal;
  }

  :deep(code) {
    @apply bg-white/10 px-1.5 py-0.5 rounded text-[0.9em];
  }

  :deep(pre) {
    @apply bg-white/10 p-3 rounded-lg my-2 overflow-x-auto;
  }

  :deep(pre code) {
    @apply bg-transparent p-0;
  }

  :deep(a) {
    @apply text-k-highlight hover:underline;
  }

  :deep(blockquote) {
    @apply border-l-2 border-k-fg-30 pl-3 my-2 text-k-fg-70;
  }
}
</style>
