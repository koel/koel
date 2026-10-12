import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from './eventBus'

describe('eventBus', () => {
  createHarness()

  it('supports high max listener count', () => {
    expect(eventBus.getMaxListeners()).toBe(100)
  })
})
