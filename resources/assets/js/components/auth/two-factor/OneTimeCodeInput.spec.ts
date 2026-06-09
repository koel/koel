import { describe, expect, it } from 'vite-plus/test'
import { screen, within } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './OneTimeCodeInput.vue'

describe('oneTimeCodeInput.vue', () => {
  const h = createHarness({ authenticated: false })

  const getBoxes = () => within(screen.getByTestId('one-time-code-input')).getAllByRole<HTMLInputElement>('textbox')

  const lastUpdate = (emitted: Record<string, unknown[]>) => {
    const updates = (emitted['update:modelValue'] as string[][]) ?? []

    return updates[updates.length - 1]?.[0] ?? ''
  }

  it('renders 6 input boxes', () => {
    h.render(Component)
    expect(getBoxes().length).toBe(6)
  })

  it('only accepts a single digit per box and advances focus', async () => {
    const { emitted } = h.render(Component)
    const boxes = getBoxes()

    await h.type(boxes[0], '1')
    await h.type(boxes[1], '2')
    await h.type(boxes[2], '3')

    expect(boxes[0].value).toBe('1')
    expect(boxes[1].value).toBe('2')
    expect(boxes[2].value).toBe('3')
    expect(lastUpdate(emitted())).toBe('123')
  })

  it('emits complete when all 6 digits are filled', async () => {
    const { emitted } = h.render(Component)
    const boxes = getBoxes()

    for (let i = 0; i < 6; i++) {
      await h.type(boxes[i], String(i + 1))
    }

    expect(emitted().complete).toEqual([['123456']])
  })

  it('distributes a paste across all 6 boxes and updates v-model', async () => {
    const { emitted } = h.render(Component)
    const boxes = getBoxes()

    const pasteEvent = new Event('paste', { bubbles: true, cancelable: true })
    Object.defineProperty(pasteEvent, 'clipboardData', {
      value: { getData: () => '987654' },
    })
    boxes[0].dispatchEvent(pasteEvent)
    await h.tick()

    expect(boxes.map(b => b.value).join('')).toBe('987654')
    expect(lastUpdate(emitted())).toBe('987654')
    expect(emitted().complete).toEqual([['987654']])
  })

  it('strips non-digits from input', async () => {
    const { emitted } = h.render(Component)
    const boxes = getBoxes()

    await h.type(boxes[0], 'a')

    expect(boxes[0].value).toBe('')
    expect(lastUpdate(emitted())).toBe('')
  })
})
