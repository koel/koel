import { describe, expect, it, vi } from 'vite-plus/test'
import { fireEvent, screen } from '@testing-library/vue'
import { ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './TagInput.vue'

describe('tagInput.vue', () => {
  const h = createHarness()

  const renderComponent = (initialTags: string[] = []) => {
    const tags = ref(initialTags)

    const { rerender } = h.render(Component, {
      props: {
        name: 'genre',
        modelValue: tags.value,
        'onUpdate:modelValue': async (value: string[]) => {
          tags.value = value
          await rerender({ modelValue: value })
        },
      },
    })

    return { tags, input: screen.getByRole('textbox', { name: 'genre' }) }
  }

  it('adds a tag on Enter', async () => {
    const { tags, input } = renderComponent(['Rock'])

    await h.user.type(input, 'Blues{Enter}')

    expect(tags.value).toEqual(['Rock', 'Blues'])
  })

  it('splits comma-separated text into tags and skips duplicates', async () => {
    const { tags, input } = renderComponent(['Rock'])

    await h.user.type(input, 'rock, Jazz,')

    expect(tags.value).toEqual(['Rock', 'Jazz'])
  })

  it('adds the pending text on blur', async () => {
    const { tags, input } = renderComponent()

    await h.user.type(input, 'Pop')
    await h.user.tab()

    expect(tags.value).toEqual(['Pop'])
  })

  it('removes a tag with its button', async () => {
    const { tags } = renderComponent(['Rock', 'Blues'])

    await h.user.click(screen.getByRole('button', { name: 'Remove Rock' }))

    expect(tags.value).toEqual(['Blues'])
  })

  it('removes the last tag on Backspace in an empty input', async () => {
    const { tags, input } = renderComponent(['Rock', 'Blues'])

    await h.user.type(input, '{Backspace}')

    expect(tags.value).toEqual(['Rock'])
  })

  it('adds the pending text on Escape', async () => {
    const { tags, input } = renderComponent(['Rock'])

    await h.user.type(input, 'Blues{Escape}')

    expect(tags.value).toEqual(['Rock', 'Blues'])
  })

  it('ignores Enter, Backspace and Escape while composing', async () => {
    const { tags, input } = renderComponent(['Rock'])

    await fireEvent.keyDown(input, { key: 'Backspace', isComposing: true })
    await h.user.type(input, 'Jazz')
    await fireEvent.keyDown(input, { key: 'Enter', isComposing: true })
    await fireEvent.keyDown(input, { key: 'Escape', isComposing: true })

    expect(tags.value).toEqual(['Rock'])
  })

  it('keeps a composing Escape from reaching its parents', async () => {
    const { input } = renderComponent()
    const parentKeydownListener = vi.fn()
    document.body.addEventListener('keydown', parentKeydownListener)

    await fireEvent.keyDown(input, { key: 'Escape', isComposing: true })

    document.body.removeEventListener('keydown', parentKeydownListener)
    expect(parentKeydownListener).not.toHaveBeenCalled()
  })
})
