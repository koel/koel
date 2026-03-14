import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { logger } from './logger'

describe('logger', () => {
  createHarness()

  it('logs with [koel] prefix', () => {
    const spy = vi.spyOn(console, 'log').mockImplementation(() => {})
    logger.log('hello')
    expect(spy).toHaveBeenCalledWith('[koel]', 'hello')
    spy.mockRestore()
  })

  it('logs errors with [koel] prefix', () => {
    const spy = vi.spyOn(console, 'error').mockImplementation(() => {})
    logger.error('something broke')
    expect(spy).toHaveBeenCalledWith('[koel]', 'something broke')
    spy.mockRestore()
  })

  it('logs info with [koel] prefix', () => {
    const spy = vi.spyOn(console, 'info').mockImplementation(() => {})
    logger.info('note')
    expect(spy).toHaveBeenCalledWith('[koel]', 'note')
    spy.mockRestore()
  })

  it('passes extra arguments through', () => {
    const spy = vi.spyOn(console, 'log').mockImplementation(() => {})
    logger.log('msg', { detail: 1 }, 'extra')
    expect(spy).toHaveBeenCalledWith('[koel]', 'msg', { detail: 1 }, 'extra')
    spy.mockRestore()
  })
})
