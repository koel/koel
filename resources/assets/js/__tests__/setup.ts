import vueSnapshotSerializer from 'jest-serializer-vue'
import { expect, vi } from 'vitest'

expect.addSnapshotSerializer(vueSnapshotSerializer)

global.ResizeObserver = global.ResizeObserver ||
  vi.fn().mockImplementation(() => ({
    disconnect: vi.fn(),
    observe: vi.fn(),
    unobserve: vi.fn()
  }))
