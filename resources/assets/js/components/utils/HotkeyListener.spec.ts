import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import { commonStore } from '@/stores/commonStore'
import { queueStore } from '@/stores/queueStore'
import Component from './HotkeyListener.vue'

const goMock = vi.fn()
const isCurrentScreenMock = vi.fn().mockReturnValue(false)
const forwardMock = vi.fn()
const rewindMock = vi.fn()
const seekToMock = vi.fn()
const openModalMock = vi.fn()

vi.mock('@/composables/useRouter', () => ({
  useRouter: () => ({
    go: goMock,
    url: (name: string) => `/#/${name}`,
    isCurrentScreen: isCurrentScreenMock,
  }),
}))

vi.mock('@/services/playbackManager', () => ({
  playback: () => ({ forward: forwardMock, rewind: rewindMock, seekTo: seekToMock }),
}))

vi.mock('@/composables/useModal', () => ({
  useModal: () => ({ openModal: openModalMock }),
}))

const pressKey = (key: string, options: KeyboardEventInit = {}) => {
  const event = new KeyboardEvent('keydown', { key, bubbles: true, ...options })
  document.body.dispatchEvent(event)
}

describe('hotkeyListener.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      vi.clearAllMocks()
      commonStore.state.uses_ai = true
    },
  })

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

  it('jumps to a tenth of the current song per digit key', () => {
    h.setReadOnlyProperty(queueStore, 'current', h.factory('song').make({ length: 200 }))
    h.render(Component)
    pressKey('5')

    expect(seekToMock).toHaveBeenCalledWith(100)
  })

  it('toggles fullscreen on Shift+F', () => {
    const emitMock = h.mock(eventBus, 'emit')
    h.setReadOnlyProperty(document, 'fullscreenEnabled', true)
    h.render(Component)
    pressKey('F', { shiftKey: true })

    expect(emitMock).toHaveBeenCalledWith('FULLSCREEN_TOGGLE')
    expect(emitMock).not.toHaveBeenCalledWith('FOCUS_SEARCH_FIELD')
  })

  it('ignores the AI Assistant shortcut when the assistant is off', () => {
    commonStore.state.uses_ai = false
    h.render(Component)
    pressKey('/')

    expect(goMock).not.toHaveBeenCalled()
  })

  it('opens the keyboard shortcuts list on "?"', () => {
    h.render(Component)
    pressKey('?', { shiftKey: true })

    expect(openModalMock).toHaveBeenCalledOnce()
  })

  it('still runs letter shortcuts with Caps Lock on', () => {
    const emitMock = h.mock(eventBus, 'emit')
    h.render(Component)
    pressKey('F')

    expect(emitMock).toHaveBeenCalledWith('FOCUS_SEARCH_FIELD')
  })

  it('toggles fullscreen, not search, on Shift+F with Caps Lock on', () => {
    const emitMock = h.mock(eventBus, 'emit')
    h.setReadOnlyProperty(document, 'fullscreenEnabled', true)
    h.render(Component)
    pressKey('f', { shiftKey: true })

    expect(emitMock).toHaveBeenCalledWith('FULLSCREEN_TOGGLE')
    expect(emitMock).not.toHaveBeenCalledWith('FOCUS_SEARCH_FIELD')
  })
})
