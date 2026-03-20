<template>
  <div ref="containerEl">
    <div class="w-full mx-auto space-y-4">
      <AiChatMessage v-for="msg in messages" :key="msg.id" :message="msg" :user="currentUser" />
      <div v-if="loading" class="flex justify-start">
        <div class="rounded-3xl px-5 py-3">
          <Icon :icon="faSpinner" spin class="text-k-fg-50" />
        </div>
      </div>
      <div ref="anchorEl" />
    </div>
  </div>
</template>

<script lang="ts" setup>
import { faSpinner } from '@fortawesome/free-solid-svg-icons'
import { nextTick, ref, watch } from 'vue'
import { useAuthorization } from '@/composables/useAuthorization'

import AiChatMessage from '@/components/ai/AiChatMessage.vue'

const { currentUser } = useAuthorization()

const props = defineProps<{
  messages: AiChatMessage[]
  loading?: boolean
}>()

const anchorEl = ref<HTMLDivElement>()

const scrollToBottom = async () => {
  await nextTick()
  anchorEl.value?.scrollIntoView({ behavior: 'smooth' })
}

watch(() => props.messages.length, scrollToBottom)
watch(() => props.loading, scrollToBottom)
</script>
