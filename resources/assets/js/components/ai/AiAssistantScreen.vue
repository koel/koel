<template>
  <div class="ai-screen fixed inset-0 z-50 flex flex-col w-screen h-screen bg-k-bg text-k-fg" @keydown.esc="goBack">
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
        <form class="relative z-10" @submit.prevent="handleSubmit">
          <div class="relative">
            <textarea
              ref="textareaEl"
              v-model="prompt"
              v-koel-focus
              class="w-full h-40 p-6 pr-14 pb-12 text-xl rounded-3xl bg-k-bg-input text-k-fg-input border border-k-fg-10 resize-none focus:outline-none focus:border-k-highlight"
              name="prompt"
              placeholder="Ask Koel to play songs, create playlists, add radio stations, and more."
              @keydown.enter.exact.prevent="handleSubmit"
            />
            <button
              :disabled="!prompt.trim()"
              class="absolute right-3 bottom-4 w-9 h-9 flex items-center justify-center rounded-full bg-k-highlight text-white disabled:opacity-30 cursor-pointer disabled:cursor-not-allowed transition-opacity"
              title="Send"
              type="submit"
            >
              <ArrowUpIcon class="w-5 h-5" />
            </button>
          </div>
        </form>

        <AiSamplePrompts v-if="!prompt.trim()" class="relative z-10 w-full mt-4" @select="selectSamplePrompt" />
      </div>
    </div>

    <!-- Chat mode -->
    <template v-else>
      <div ref="chatContainerEl" class="flex-1 overflow-y-auto p-8 pb-4">
        <div class="w-full max-w-4xl mx-auto space-y-4">
          <AiChatMessage v-for="msg in messages" :key="msg.id" :message="msg" />
          <div v-if="loading" class="flex justify-start">
            <div class="rounded-2xl px-4 py-3 bg-white/5 text-k-fg-50">
              <Icon :icon="faSpinner" spin />
            </div>
          </div>
          <div ref="messagesEndEl" />
        </div>
      </div>

      <div class="shrink-0 p-4 pt-2 border-t border-k-fg-10">
        <form class="w-full max-w-4xl mx-auto" @submit.prevent="handleSubmit">
          <div class="relative">
            <textarea
              ref="textareaEl"
              v-model="prompt"
              v-koel-focus
              :disabled="loading"
              class="w-full p-4 pr-14 text-base rounded-2xl bg-k-bg-input text-k-fg-input border border-k-fg-10 resize-none focus:outline-none focus:border-k-highlight disabled:opacity-50 disabled:cursor-not-allowed"
              name="prompt"
              placeholder="Send a message…"
              rows="1"
              @keydown.enter.exact.prevent="handleSubmit"
              @input="autoResize"
            />
            <button
              :disabled="!prompt.trim() || loading"
              class="absolute right-3 bottom-3 w-8 h-8 flex items-center justify-center rounded-full bg-k-highlight text-white disabled:opacity-30 cursor-pointer disabled:cursor-not-allowed transition-opacity"
              title="Send"
              type="submit"
            >
              <Icon v-if="loading" :icon="faSpinner" spin />
              <ArrowUpIcon v-else class="w-4 h-4" />
            </button>
          </div>
        </form>
      </div>
    </template>
  </div>
</template>

<script lang="ts" setup>
import { faSpinner } from '@fortawesome/free-solid-svg-icons'
import { ArrowUpIcon, XIcon } from 'lucide-vue-next'
import { nextTick, ref, watch } from 'vue'
import { playback } from '@/services/playbackManager'
import { queueStore } from '@/stores/queueStore'
import { useAiChat } from '@/composables/useAiChat'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useRouter } from '@/composables/useRouter'
import { useErrorHandler } from '@/composables/useErrorHandler'

import AiChatMessage from '@/components/ai/AiChatMessage.vue'
import AiSamplePrompts from '@/components/ai/AiSamplePrompts.vue'

const { toastSuccess } = useMessageToaster()
const { go, url } = useRouter()
const { handleHttpError } = useErrorHandler()
const { messages, loading, hasMessages, sendPrompt } = useAiChat()

const textareaEl = ref<HTMLTextAreaElement>()
const messagesEndEl = ref<HTMLDivElement>()
const chatContainerEl = ref<HTMLDivElement>()
const prompt = ref('')

const goBack = () => go(-1)

const scrollToBottom = async () => {
  await nextTick()
  messagesEndEl.value?.scrollIntoView({ behavior: 'smooth' })
}

const autoResize = () => {
  const el = textareaEl.value

  if (el) {
    el.style.height = 'auto'
    el.style.height = `${Math.min(el.scrollHeight, 120)}px`
  }
}

const selectSamplePrompt = async (text: string) => {
  prompt.value = text
  await nextTick()
  textareaEl.value?.focus()
}

watch(() => messages.value.length, scrollToBottom)

const handleSubmit = async () => {
  if (!prompt.value.trim() || loading.value) {
    return
  }

  const text = prompt.value.trim()
  prompt.value = ''

  await nextTick()

  if (textareaEl.value) {
    textareaEl.value.style.height = 'auto'
  }

  try {
    const result = await sendPrompt(text)

    if (!result) {
      return
    }

    if (result.action === 'create_smart_playlist') {
      toastSuccess(`Playlist "${result.resource.name}" created.`)
      go(url('playlists.show', { id: result.resource.id }))
    } else if (result.action === 'add_radio_station') {
      toastSuccess(`Station "${result.resource.name}" added.`)
      go(url('radio-stations.index'))
    } else if (result.action === 'play_radio_station') {
      await playback('radio').play(result.resource)
      go(url('queue'))
    } else if (result.action === 'play_songs') {
      if (result.queue) {
        queueStore.queue(result.resource)
      } else {
        await playback().queueAndPlay(result.resource)
      }

      go(url('queue'))
    }
  } catch (e: unknown) {
    handleHttpError(e)
  }
}
</script>

<style lang="postcss" scoped>
.ai-screen::before {
  content: '';
  position: fixed;
  inset: 0;
  background: #000;
  opacity: 0;
  transition: opacity 0.5s ease;
  pointer-events: none;
  z-index: 0;
}

.ai-screen:has(textarea:focus)::before {
  opacity: 0.5;
}
</style>
