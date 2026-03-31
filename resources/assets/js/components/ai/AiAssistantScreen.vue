<template>
  <div
    class="ai-screen fixed inset-0 z-50 px-2 flex overflow-hidden flex-col w-screen h-screen bg-k-bg text-xl text-k-fg"
    @keydown.esc="goBack"
  >
    <button
      class="fixed top-4 right-4 z-20 text-k-fg-50 hover:text-k-fg transition-colors cursor-pointer"
      title="Close"
      type="button"
      @click="goBack"
    >
      <XIcon class="w-6 h-6" />
    </button>

    <!-- Initial state: centered prompt -->
    <div v-if="!hasMessages && !loading" class="flex-1 flex flex-col items-center p-8">
      <div class="w-full max-w-4xl my-auto">
        <AiPromptBox ref="promptBoxEl" class="relative z-10" @submit="handleSubmit" />
        <AiSamplePrompts class="relative z-10 w-full mt-4" @select="selectSamplePrompt" />
      </div>
    </div>

    <!-- Chat mode -->
    <template v-else>
      <section v-koel-overflow-fade class="py-6 flex-1 flex flex-col w-full max-w-4xl overflow-auto mx-auto">
        <AiChatHistory :messages :loading class="flex-1" />
      </section>

      <div class="shrink-0 p-4 pt-2">
        <AiPromptBox
          ref="promptBoxEl"
          mode="chat"
          :disabled="loading"
          class="w-full max-w-4xl mx-auto"
          @submit="handleSubmit"
        />
      </div>
    </template>
  </div>
</template>

<script lang="ts" setup>
import { XIcon } from 'lucide-vue-next'
import { nextTick, ref } from 'vue'
import { playback } from '@/services/playbackManager'
import { queueStore } from '@/stores/queueStore'
import { useAiChat } from '@/composables/useAiChat'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useRouter } from '@/composables/useRouter'
import { useErrorHandler } from '@/composables/useErrorHandler'

import AiChatHistory from '@/components/ai/AiChatHistory.vue'
import AiPromptBox from '@/components/ai/AiPromptBox.vue'
import AiSamplePrompts from '@/components/ai/AiSamplePrompts.vue'

const { toastSuccess } = useMessageToaster()
const { go, onScreenActivated, url } = useRouter()
const { handleHttpError } = useErrorHandler()
const { messages, loading, hasMessages, sendPrompt } = useAiChat()

const promptBoxEl = ref<InstanceType<typeof AiPromptBox>>()

const goBack = () => go(-1)

onScreenActivated('AI', async () => {
  await nextTick()
  promptBoxEl.value?.focus()
})

const selectSamplePrompt = (text: string) => promptBoxEl.value?.fill(text)

const handleSubmit = async (text: string) => {
  try {
    const result = await sendPrompt(text)

    if (!result) {
      return
    }

    if (result.action === 'create_smart_playlist') {
      toastSuccess(`Playlist "${result.resource.name}" created.`)
    } else if (result.action === 'add_radio_station') {
      toastSuccess(`Station "${result.resource.name}" added.`)
    } else if (result.action === 'play_radio_station') {
      await playback('radio').play(result.resource)
    } else if (result.action === 'play_songs') {
      if (result.queue) {
        queueStore.queue(result.resource)
      } else {
        await playback().queueAndPlay(result.resource)
      }
    }
  } catch (e: unknown) {
    handleHttpError(e)
  } finally {
    await nextTick()
    promptBoxEl.value?.focus()
  }
}
</script>

<style lang="postcss" scoped>
.ai-screen::after {
  content: '';
  position: fixed;
  inset: 0;
  background: #000;
  opacity: 0.1;
  pointer-events: none;
  z-index: -1;
}
</style>
