import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import Component from './KeyboardShortcutsModal.vue'

describe('keyboardShortcutsModal.vue', () => {
  const h = createHarness()

  const shortcutKeysInGroup = (group: string) =>
    Array.from(screen.getByTestId(`shortcut-group-${group}`).querySelectorAll('dd')).map(keys =>
      Array.from(keys.querySelectorAll('kbd')).map(key => key.textContent),
    )

  it('lists the AI Assistant shortcut when the assistant is on', () => {
    commonStore.state.uses_ai = true
    h.render(Component)

    expect(shortcutKeysInGroup('Go to')).toContainEqual(['/'])
  })

  it('leaves out the AI Assistant shortcut when the assistant is off', () => {
    commonStore.state.uses_ai = false
    h.render(Component)

    expect(shortcutKeysInGroup('Go to')).not.toContainEqual(['/'])
  })

  it('closes on Escape', async () => {
    const { emitted } = h.render(Component)
    screen.getByTestId('keyboard-shortcuts').focus()

    await h.user.keyboard('{Escape}')

    expect(emitted().close).toBeTruthy()
  })

  it('closes with the close button', async () => {
    const { emitted } = h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Close' }))

    expect(emitted().close).toBeTruthy()
  })
})
