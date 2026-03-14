import { screen } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { copyText } from '@/utils/helpers'
import Component from './AiChatMessage.vue'

vi.mock('@/utils/helpers', async original => {
  const mod = await original<typeof import('@/utils/helpers')>()
  return { ...mod, copyText: vi.fn() }
})

describe('aiChatMessage', () => {
  const h = createHarness()

  it('renders assistant message content', () => {
    h.render(Component, {
      props: {
        message: { id: '1', role: 'assistant', content: 'Here is some jazz.', error: false },
      },
    })

    expect(screen.getByText('Here is some jazz.')).toBeTruthy()
  })

  it('renders user message content', () => {
    h.render(Component, {
      props: {
        message: { id: '2', role: 'user', content: 'Play some jazz', error: false },
      },
    })

    expect(screen.getByText('Play some jazz')).toBeTruthy()
  })

  it('shows copy button for assistant messages', () => {
    h.render(Component, {
      props: {
        message: { id: '1', role: 'assistant', content: 'Hello!', error: false },
      },
    })

    expect(screen.getByRole('button', { name: /copy/i })).toBeTruthy()
  })

  it('does not show copy button for user messages', () => {
    h.render(Component, {
      props: {
        message: { id: '2', role: 'user', content: 'Play jazz', error: false },
      },
    })

    expect(screen.queryByRole('button', { name: /copy/i })).toBeNull()
  })

  it('does not show copy button for error messages', () => {
    h.render(Component, {
      props: {
        message: { id: '3', role: 'assistant', content: 'Something went wrong.', error: true },
      },
    })

    expect(screen.queryByRole('button', { name: /copy/i })).toBeNull()
  })

  it('copies message content to clipboard when copy button is clicked', async () => {
    h.render(Component, {
      props: {
        message: { id: '1', role: 'assistant', content: 'Here are your songs.', error: false },
      },
    })

    await h.user.click(screen.getByRole('button', { name: /copy/i }))

    expect(copyText).toHaveBeenCalledWith('Here are your songs.')
  })
})
