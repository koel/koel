import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { logger } from '@/utils/logger'
import { lastWriteWins } from './lastWriteWins'

describe('lastWriteWins', () => {
  const h = createHarness()

  it('waits for the running send and then sends only the newest waiting value', async () => {
    let finishFirstSend: () => void = () => {}
    const send = vi
      .fn()
      .mockImplementationOnce(
        () =>
          new Promise<void>(resolve => {
            finishFirstSend = resolve
          }),
      )
      .mockResolvedValue(undefined)

    const saveLatest = lastWriteWins(send)

    const done = saveLatest('first')
    saveLatest('second')
    saveLatest('third')

    expect(send).toHaveBeenCalledTimes(1)

    finishFirstSend()
    await done

    expect(send.mock.calls).toEqual([['first'], ['third']])
  })

  it('logs a failed send and keeps going with the next value', async () => {
    const logMock = h.mock(logger, 'error')
    const send = vi.fn().mockRejectedValueOnce(new Error('offline')).mockResolvedValue(undefined)
    const saveLatest = lastWriteWins(send)

    const done = saveLatest('first')
    saveLatest('second')
    await done

    expect(logMock).toHaveBeenCalled()
    expect(send).toHaveBeenLastCalledWith('second')
  })

  it('tells whether the newest value was saved', async () => {
    h.mock(logger, 'error')
    const saveLatest = lastWriteWins(vi.fn().mockRejectedValueOnce(new Error('offline')))

    await expect(saveLatest('lost')).resolves.toBe(false)
    await expect(saveLatest('kept')).resolves.toBe(true)
  })

  it('keeps going after a send that throws before returning a promise', async () => {
    h.mock(logger, 'error')
    const send = vi
      .fn()
      .mockImplementationOnce(() => {
        throw new Error('broken')
      })
      .mockResolvedValue(undefined)
    const saveLatest = lastWriteWins(send)

    await saveLatest('first')
    await saveLatest('second')

    expect(send).toHaveBeenLastCalledWith('second')
  })
})
