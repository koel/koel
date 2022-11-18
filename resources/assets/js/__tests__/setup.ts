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

Axios.defaults.adapter = vi.fn()
