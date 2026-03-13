import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Component from './HotkeyListener.vue'

const goMock = vi.fn()
const isCurrentScreenMock = vi.fn().mockReturnValue(false)

vi.mock('@/composables/useRouter', () => ({
  useRouter: () => ({
    go: goMock,
    url: (name: string) => `/#/${name}`,
    isCurrentScreen: isCurrentScreenMock,
  }),
}))

const pressKey = (key: string) => {
  const event = new KeyboardEvent('keydown', { key, bubbles: true })
  document.body.dispatchEvent(event)
}

describe('hotkeyListener.vue', () => {
  const h = createHarness()

  it('emits FOCUS_SEARCH_FIELD on "f" key', () => {
    const emitMock = h.mock(eventBus, 'emit')

    h.render(Component)
    pressKey('f')

    expect(emitMock).toHaveBeenCalledWith('FOCUS_SEARCH_FIELD')
  })

  it('navigates to home on "h" key', () => {
    h.render(Component)
    pressKey('h')

    expect(goMock).toHaveBeenCalledWith('/#/home')
  })
})
