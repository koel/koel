import { computed, ref } from 'vue'
import { aiService } from '@/services/aiService'
import { queueStore } from '@/stores/queueStore'
import { radioStationStore } from '@/stores/radioStationStore'

let idCounter = 0

const messages = ref<AiChatMessage[]>([])
const loading = ref(false)

export const useAiChat = () => {
  const hasMessages = computed(() => messages.value.length > 0)

  const addUserMessage = (text: string) => {
    messages.value.push({
      id: `msg-${++idCounter}`,
      role: 'user',
      content: text,
      error: false,
    })
  }

  const addAssistantMessage = (text: string, error = false) => {
    messages.value.push({
      id: `msg-${++idCounter}`,
      role: 'assistant',
      content: text,
      error,
    })
  }

  const sendPrompt = async (text: string) => {
    addUserMessage(text)
    loading.value = true

    try {
      const response = await aiService.prompt(text, {
        songId: queueStore.current?.id,
        radioStationId: radioStationStore.current?.id,
      })

      const result = aiService.handleResponse(response)
      addAssistantMessage(result.message)

      return result
    } catch (e: unknown) {
      addAssistantMessage('Something went wrong. Please try again.', true)
      throw e
    } finally {
      loading.value = false
    }
  }

  const clearHistory = () => {
    messages.value = []
    aiService.resetConversation()
  }

  return {
    messages,
    loading,
    hasMessages,
    sendPrompt,
    clearHistory,
  }
}
