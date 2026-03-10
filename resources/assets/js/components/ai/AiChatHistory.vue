<template>
  <div ref="containerEl" class="overflow-y-auto">
    <div class="w-full max-w-4xl mx-auto space-y-4">
      <AiChatMessage v-for="msg in messages" :key="msg.id" :message="msg" />
      <div v-if="loading" class="flex justify-start">
        <div class="rounded-2xl px-4 py-3 bg-white/5 text-k-fg-50">
          <Icon :icon="faSpinner" spin />
        </div>
      </div>
      <div ref="anchorEl" />
    </div>
  </div>
</template>

<script lang="ts" setup>
import { faSpinner } from '@fortawesome/free-solid-svg-icons'
import { nextTick, ref, watch } from 'vue'

import AiChatMessage from '@/components/ai/AiChatMessage.vue'

const props = defineProps<{
  messages: AiChatMessage[]
  loading: boolean
}>()

const anchorEl = ref<HTMLDivElement>()

const scrollToBottom = async () => {
  await nextTick()
  anchorEl.value?.scrollIntoView({ behavior: 'smooth' })
}

watch(() => props.messages.length, scrollToBottom)
</script>
