import UnitTestCase from '@/__tests__/UnitTestCase'
import { expect, it, vi } from 'vitest'
import { eventBus } from './eventBus'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => eventBus.all = new Map());
  }

  protected test () {
    it('listens on a single event', () => {
      const mock = vi.fn()
      eventBus.on('KOEL_READY', mock)

      eventBus.emit('KOEL_READY')

      expect(mock).toHaveBeenCalledOnce()
    })

    it('listens with parameters', () => {
      const mock = vi.fn()
      eventBus.on('KOEL_READY', mock)

      eventBus.emit('KOEL_READY', 'foo', 'bar')

      expect(mock).toHaveBeenNthCalledWith(1, 'foo', 'bar')
    })

    it('registers multiple listeners at once', () => {
      const mock1 = vi.fn()
      const mock2 = vi.fn()

      eventBus.on({
        KOEL_READY: mock1,
        MODAL_SHOW_ABOUT_KOEL: mock2
      })

      eventBus.emit('KOEL_READY')
      expect(mock1).toHaveBeenCalledOnce()

      eventBus.emit('MODAL_SHOW_ABOUT_KOEL')
      expect(mock2).toHaveBeenCalledOnce()
    })

    it('queue up listeners on same event', () => {
      const mock1 = vi.fn()
      const mock2 = vi.fn()
      eventBus.on('KOEL_READY', mock1)
      eventBus.on('KOEL_READY', mock2)

      eventBus.emit('KOEL_READY')

      expect(mock1).toHaveBeenCalledOnce()
      expect(mock2).toHaveBeenCalledOnce()
    })
  }
}
