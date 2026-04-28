import vueSnapshotSerializer from 'jest-serializer-vue'
import { expect, vi } from 'vite-plus/test'

declare global {
  interface Window {
    BASE_URL: string
    MAILER_CONFIGURED: boolean
    SSO_PROVIDERS: SSOProvider[]
    BRANDING: Branding
    createLemonSqueezy?: () => Closure
    RUNNING_UNIT_TESTS?: boolean
  }

  interface LemonSqueezy {
    Url: {
      Open: () => void
    }
  }
}

expect.addSnapshotSerializer(vueSnapshotSerializer)

globalThis.ResizeObserver =
  globalThis.ResizeObserver ||
  vi.fn().mockImplementation(function () {
    return {
      disconnect: vi.fn(),
      observe: vi.fn(),
      unobserve: vi.fn(),
    }
  })

globalThis.LemonSqueezy = {
  Url: {
    Open: vi.fn(),
  },
}

HTMLMediaElement.prototype.load = vi.fn()
HTMLMediaElement.prototype.play = vi.fn()
HTMLMediaElement.prototype.pause = vi.fn()

HTMLDialogElement.prototype.show = vi.fn(function mock(this: HTMLDialogElement) {
  this.open = true
})

HTMLDialogElement.prototype.showModal = vi.fn(function mock(this: HTMLDialogElement) {
  this.open = true
})

HTMLDialogElement.prototype.close = vi.fn(function mock(this: HTMLDialogElement) {
  this.open = false
})

if (typeof globalThis.ToggleEvent === 'undefined') {
  class ToggleEventShim extends Event {
    oldState: string
    newState: string
    constructor(type: string, init: EventInit & { oldState?: string; newState?: string } = {}) {
      super(type, init)
      this.oldState = init.oldState ?? 'closed'
      this.newState = init.newState ?? 'closed'
    }
  }
  ;(globalThis as unknown as { ToggleEvent: typeof ToggleEventShim }).ToggleEvent = ToggleEventShim
}

if (!('popover' in HTMLElement.prototype)) {
  const popoverOpen = new WeakMap<HTMLElement, boolean>()

  Object.defineProperty(HTMLElement.prototype, 'popover', {
    configurable: true,
    get(this: HTMLElement) {
      return this.getAttribute('popover')
    },
    set(this: HTMLElement, value: string | null) {
      value === null ? this.removeAttribute('popover') : this.setAttribute('popover', String(value))
    },
  })

  HTMLElement.prototype.showPopover = function (this: HTMLElement) {
    if (popoverOpen.get(this)) {
      return
    }
    this.dispatchEvent(new ToggleEvent('beforetoggle', { oldState: 'closed', newState: 'open' }))
    popoverOpen.set(this, true)
    this.dispatchEvent(new ToggleEvent('toggle', { oldState: 'closed', newState: 'open' }))
  }

  HTMLElement.prototype.hidePopover = function (this: HTMLElement) {
    if (!popoverOpen.get(this)) {
      return
    }
    this.dispatchEvent(new ToggleEvent('beforetoggle', { oldState: 'open', newState: 'closed' }))
    popoverOpen.set(this, false)
    this.dispatchEvent(new ToggleEvent('toggle', { oldState: 'open', newState: 'closed' }))
  }

  HTMLElement.prototype.togglePopover = function (this: HTMLElement) {
    popoverOpen.get(this) ? this.hidePopover() : this.showPopover()
  }
}

window.BASE_URL = 'http://test/'
window.MAILER_CONFIGURED = true
window.SSO_PROVIDERS = []
window.RUNNING_UNIT_TESTS = true

window.createLemonSqueezy = vi.fn()

Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation(query => ({
    matches: true,
    media: query,
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
})

// Mock iframe's navigation API
const iframeContentWindowMap = new WeakMap<HTMLIFrameElement, any>()

Object.defineProperty(HTMLIFrameElement.prototype, 'contentWindow', {
  configurable: true,
  get(this: HTMLIFrameElement) {
    if (!iframeContentWindowMap.has(this)) {
      const stub = {
        location: {
          replace: vi.fn(),
          assign: vi.fn(),
          reload: vi.fn(),
          href: '',
        },
        postMessage: vi.fn(),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
      }
      iframeContentWindowMap.set(this, stub)
    }
    return iframeContentWindowMap.get(this)
  },
})

window.IntersectionObserver = vi.fn().mockImplementation(function () {
  return {
    observe: vi.fn(),
    unobserve: vi.fn(),
    disconnect: vi.fn(),
  }
})
