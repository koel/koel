import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Component from './HotkeyListener.vue'

const goMock = vi.fn()
const isCurrentScreenMock = vi.fn().mockReturnValue(false)
const forwardMock = vi.fn()
const rewindMock = vi.fn()

vi.mock('@/composables/useRouter', () => ({
  useRouter: () => ({
    go: goMock,
    url: (name: string) => `/#/${name}`,
    isCurrentScreen: isCurrentScreenMock,
  }),
}))

vi.mock('@/services/playbackManager', () => ({
  playback: () => ({ forward: forwardMock, rewind: rewindMock }),
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

  it('seeks forward on ArrowRight', () => {
    h.render(Component)
    pressKey('ArrowRight')

    expect(forwardMock).toHaveBeenCalledWith(10)
  })

  it('seeks backward on ArrowLeft', () => {
    h.render(Component)
    pressKey('ArrowLeft')

    expect(rewindMock).toHaveBeenCalledWith(10)
  })
})
