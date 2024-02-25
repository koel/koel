import vueSnapshotSerializer from 'jest-serializer-vue'
import { expect, vi } from 'vitest'
import Axios from 'axios'

declare global {
  interface Window {
    BASE_URL: string
    MAILER_CONFIGURED: boolean
    createLemonSqueezy: () => void
  }

  interface LemonSqueezy {
    Url: {
      Open: () => void
    }
  }
}

expect.addSnapshotSerializer(vueSnapshotSerializer)

global.ResizeObserver = global.ResizeObserver ||
  vi.fn().mockImplementation(() => ({
    disconnect: vi.fn(),
    observe: vi.fn(),
    unobserve: vi.fn()
  }))


global.LemonSqueezy = {
  Url: {
    Open: vi.fn()
  }
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

window.createLemonSqueezy = vi.fn()

Axios.defaults.adapter = vi.fn()
