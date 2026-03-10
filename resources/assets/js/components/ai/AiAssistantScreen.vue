<template>
  <div
    class="ai-screen fixed inset-0 z-50 flex flex-col w-screen h-screen overflow-hidden bg-k-bg text-k-fg"
    @keydown.esc="goBack"
  >
    <button
      class="absolute top-4 right-4 z-10 text-k-fg-50 hover:text-k-fg transition-colors cursor-pointer"
      title="Close"
      type="button"
      @click="goBack"
    >
      <XIcon class="w-6 h-6" />
    </button>

    <div class="flex-1 flex flex-col items-center justify-center px-8">
      <div class="w-full max-w-4xl">
        <form class="relative z-10" @submit.prevent="handleSubmit">
          <div class="relative">
            <textarea
              ref="textareaEl"
              v-model="prompt"
              v-koel-focus
              :disabled="loading"
              class="w-full h-40 p-6 pr-14 pb-12 text-xl rounded-3xl bg-k-bg-input text-k-fg-input border border-k-fg-10 resize-none focus:outline-none focus:border-k-highlight disabled:opacity-50 disabled:cursor-not-allowed"
              name="prompt"
              placeholder="Ask Koel to play songs, create playlists, add radio stations, and more."
              @keydown.enter.exact.prevent="handleSubmit"
            />
            <button
              :disabled="!prompt.trim() || loading"
              class="absolute right-3 bottom-4 w-9 h-9 flex items-center justify-center rounded-full bg-k-highlight text-white disabled:opacity-30 cursor-pointer disabled:cursor-not-allowed transition-opacity"
              title="Send"
              type="submit"
            >
              <Icon v-if="loading" :icon="faSpinner" spin />
              <ArrowUpIcon v-else class="w-5 h-5" />
            </button>
          </div>
        </form>

        <div
          v-if="displayedMessage"
          class="relative z-10 w-full mt-4 text-lg px-4 py-2 rounded-lg ai-response"
          :class="error ? 'bg-red-500/10 text-red-400' : 'bg-k-highlight/10 text-k-fg'"
          v-html="revealedHtml"
        />

        <AiSamplePrompts
          v-if="!prompt.trim() && !displayedMessage"
          class="relative z-10 w-full mt-4"
          @select="selectSamplePrompt"
        />
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { faSpinner } from '@fortawesome/free-solid-svg-icons'
import { ArrowUpIcon, XIcon } from 'lucide-vue-next'
import { computed, nextTick, ref } from 'vue'
import { aiService } from '@/services/aiService'
import { playback } from '@/services/playbackManager'
import { queueStore } from '@/stores/queueStore'
import { radioStationStore } from '@/stores/radioStationStore'
import { simpleMarkdownToHtml } from '@/utils/formatters'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useRouter } from '@/composables/useRouter'
import { useErrorHandler } from '@/composables/useErrorHandler'

import AiSamplePrompts from '@/components/ai/AiSamplePrompts.vue'

const { toastSuccess } = useMessageToaster()
const { go, url } = useRouter()
const { handleHttpError } = useErrorHandler()

const textareaEl = ref<HTMLTextAreaElement>()
const prompt = ref('')
const loading = ref(false)
const displayedMessage = ref('')
const error = ref(false)

const revealedHtml = computed(() => {
  if (!displayedMessage.value) {
    return ''
  }

  const html = simpleMarkdownToHtml(displayedMessage.value)
  let wordIndex = 0

  // Split on word boundaries while preserving HTML tags intact
  return html.replace(/(<[^>]+>)|(\S+)/g, (match, tag) => {
    if (tag) {
      return match // HTML tags pass through unchanged
    }

    const delay = wordIndex * 60
    wordIndex++
    return `<span class="ai-word" style="animation-delay:${delay}ms">${match}</span>`
  })
})

const goBack = () => go(-1)

const selectSamplePrompt = async (text: string) => {
  prompt.value = text
  await nextTick()
  textareaEl.value?.focus()
}

const handleSubmit = async () => {
  if (!prompt.value.trim() || loading.value) {
    return
  }

  loading.value = true
  displayedMessage.value = ''
  error.value = false

  try {
    const response = await aiService.prompt(prompt.value, {
      songId: queueStore.current?.id,
      radioStationId: radioStationStore.current?.id,
    })
    const result = aiService.handleResponse(response)

    displayedMessage.value = result.message

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
  } finally {
    loading.value = false
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

.ai-response :deep(.ai-word) {
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
