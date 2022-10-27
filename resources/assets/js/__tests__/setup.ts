declare global {
  interface Window {
    BASE_URL: string;
  }
}

import vueSnapshotSerializer from 'jest-serializer-vue'
import { expect, vi } from 'vitest'
import Axios from 'axios'

expect.addSnapshotSerializer(vueSnapshotSerializer)

global.ResizeObserver = global.ResizeObserver ||
  vi.fn().mockImplementation(() => ({
    disconnect: vi.fn(),
    observe: vi.fn(),
    unobserve: vi.fn()
  }))

window.HTMLMediaElement.prototype.load = vi.fn()
window.HTMLMediaElement.prototype.play = vi.fn()
window.HTMLMediaElement.prototype.pause = vi.fn()

window.HTMLDialogElement.prototype.show = vi.fn()
window.HTMLDialogElement.prototype.showModal = vi.fn()
window.HTMLDialogElement.prototype.close = vi.fn()

window.BASE_URL = 'http://test/'

Axios.defaults.adapter = vi.fn()
