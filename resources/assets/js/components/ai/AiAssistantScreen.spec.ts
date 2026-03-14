import { screen, waitFor } from '@testing-library/vue'
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { aiService } from '@/services/aiService'
import { useAiChat } from '@/composables/useAiChat'
import Router from '@/router'
import Component from './AiAssistantScreen.vue'

describe('aiAssistantScreen.vue', () => {
  const h = createHarness()

  beforeEach(() => {
    Element.prototype.scrollIntoView = vi.fn()
    useAiChat().clearHistory()
  })

  it('renders the prompt textarea', () => {
    h.render(Component)
    screen.getByPlaceholderText('Ask Koel to play songs, create playlists, add radio stations, and more.')
  })

  it('shows sample prompts when empty', () => {
    h.render(Component)
    expect(screen.getAllByRole('button').length).toBeGreaterThan(1)
  })

  it('submits a prompt and transitions to chat mode', async () => {
    const response: AiResponse = {
      message: 'Playing some jazz for you.',
      action: null,
      conversation_id: 'conv-1',
      data: {},
    }

    h.mock(aiService, 'prompt').mockResolvedValue(response)
    h.mock(aiService, 'handleResponse').mockReturnValue({
      message: 'Playing some jazz for you.',
      action: null,
      resource: undefined,
    })

    h.render(Component)

    await h.type(screen.getByRole('textbox'), 'Play some jazz')
    await h.user.click(screen.getByTitle('Send'))

    await waitFor(() => {
      expect(aiService.prompt).toHaveBeenCalledWith('Play some jazz', expect.objectContaining({}))
      const messages = document.querySelectorAll('.ai-message')
      const texts = Array.from(messages).map(el => el.textContent)
      expect(texts.some(t => t?.includes('Play some jazz'))).toBe(true)
      expect(texts.some(t => t?.includes('Playing some jazz'))).toBe(true)
    })
  })

  it('does not submit when prompt is empty', async () => {
    const mock = h.mock(aiService, 'prompt')
    h.render(Component)

    await h.user.click(screen.getByTitle('Send'))

    expect(mock).not.toHaveBeenCalled()
  })

  it('navigates back when close button is clicked', async () => {
    const mock = h.mock(Router, 'go')
    h.render(Component)

    await h.user.click(screen.getByTitle('Close'))

    expect(mock).toHaveBeenCalledWith(-1)
  })

  it('populates textarea when a sample prompt is selected', async () => {
    h.render(Component)

    const sampleButton = screen.getAllByRole('button').find(btn => !['Send', 'Close'].includes(btn.title))!
    const promptText = sampleButton.textContent!.trim()
    await h.user.click(sampleButton)

    expect(screen.getByRole<HTMLTextAreaElement>('textbox').value).toBe(promptText)
  })

  it('shows chat input at bottom after first message', async () => {
    const response: AiResponse = {
      message: 'Done.',
      action: null,
      conversation_id: 'conv-1',
      data: {},
    }

    h.mock(aiService, 'prompt').mockResolvedValue(response)
    h.mock(aiService, 'handleResponse').mockReturnValue({
      message: 'Done.',
      action: null,
      resource: undefined,
    })

    h.render(Component)

    await h.type(screen.getByRole('textbox'), 'Hello')
    await h.user.click(screen.getByTitle('Send'))

    await waitFor(() => {
      screen.getByPlaceholderText('Send a message…')
    })
  })
})
