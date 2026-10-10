import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { logger } from '@/utils/logger'
import { createInOrderSender } from './inOrderSender'

describe('createInOrderSender', () => {
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

    const sendInOrder = createInOrderSender(send)

    const done = sendInOrder('first')
    sendInOrder('second')
    sendInOrder('third')

    expect(send).toHaveBeenCalledTimes(1)

    finishFirstSend()
    await done

    expect(send.mock.calls).toEqual([['first'], ['third']])
  })

  it('logs a failed send and keeps going with the next value', async () => {
    const logMock = h.mock(logger, 'error')
    const send = vi.fn().mockRejectedValueOnce(new Error('offline')).mockResolvedValue(undefined)
    const sendInOrder = createInOrderSender(send)

    const done = sendInOrder('first')
    sendInOrder('second')
    await done

    expect(logMock).toHaveBeenCalled()
    expect(send).toHaveBeenLastCalledWith('second')
  })

  it('tells whether the newest value was saved', async () => {
    h.mock(logger, 'error')
    const sendInOrder = createInOrderSender(vi.fn().mockRejectedValueOnce(new Error('offline')))

    await expect(sendInOrder('lost')).resolves.toBe(false)
    await expect(sendInOrder('kept')).resolves.toBe(true)
  })

  it('keeps going after a send that throws before returning a promise', async () => {
    h.mock(logger, 'error')
    const send = vi
      .fn()
      .mockImplementationOnce(() => {
        throw new Error('broken')
      })
      .mockResolvedValue(undefined)
    const sendInOrder = createInOrderSender(send)

    await sendInOrder('first')
    await sendInOrder('second')

    expect(send).toHaveBeenLastCalledWith('second')
  })
})
