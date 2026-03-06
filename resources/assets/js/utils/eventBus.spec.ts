import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from './eventBus'

describe('eventBus', () => {
  createHarness({
    beforeEach: () => {
      eventBus.removeAllListeners()
    },
  })

  it('emits and receives events', () => {
    const callback = vi.fn()
    eventBus.on('LOG_OUT', callback)
    eventBus.emit('LOG_OUT')
    expect(callback).toHaveBeenCalledOnce()
  })

  it('passes payload to listeners', () => {
    const callback = vi.fn()
    eventBus.on('SEARCH_KEYWORDS_CHANGED', callback)
    eventBus.emit('SEARCH_KEYWORDS_CHANGED', 'test query')
    expect(callback).toHaveBeenCalledWith('test query')
  })

  it('supports multiple listeners', () => {
    const cb1 = vi.fn()
    const cb2 = vi.fn()
    eventBus.on('LOG_OUT', cb1)
    eventBus.on('LOG_OUT', cb2)
    eventBus.emit('LOG_OUT')
    expect(cb1).toHaveBeenCalledOnce()
    expect(cb2).toHaveBeenCalledOnce()
  })

  it('removes listeners', () => {
    const callback = vi.fn()
    eventBus.on('LOG_OUT', callback)
    eventBus.removeAllListeners('LOG_OUT')
    eventBus.emit('LOG_OUT')
    expect(callback).not.toHaveBeenCalled()
  })

  it('supports high max listener count', () => {
    expect(eventBus.getMaxListeners()).toBe(100)
  })
})
