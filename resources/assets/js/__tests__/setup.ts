import vueSnapshotSerializer from 'jest-serializer-vue'
import { expect, vi } from 'vitest'
import Axios from 'axios'

declare global {
  interface Window {
    BASE_URL: string
    MAILER_CONFIGURED: boolean
    SSO_PROVIDERS: string[]
    createLemonSqueezy: () => void
  }

  interface LemonSqueezy {
    Url: {
      Open: () => void
    }
  }
}

expect.addSnapshotSerializer(vueSnapshotSerializer)

globalThis.ResizeObserver = globalThis.ResizeObserver
|| vi.fn().mockImplementation(() => ({
  disconnect: vi.fn(),
  observe: vi.fn(),
  unobserve: vi.fn(),
}))

globalThis.LemonSqueezy = {
  Url: {
    Open: vi.fn(),
  },
}

HTMLMediaElement.prototype.load = vi.fn()
HTMLMediaElement.prototype.play = vi.fn()
HTMLMediaElement.prototype.pause = vi.fn()

HTMLDialogElement.prototype.show = vi.fn(function mock () {
  this.open = true
})

HTMLDialogElement.prototype.showModal = vi.fn(function mock () {
  this.open = true
})

HTMLDialogElement.prototype.close = vi.fn(function mock () {
  this.open = false
})

window.BASE_URL = 'http://test/'
window.MAILER_CONFIGURED = true
window.SSO_PROVIDERS = []

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

Axios.defaults.adapter = vi.fn()
