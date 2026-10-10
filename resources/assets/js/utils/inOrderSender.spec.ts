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
      .mockImplementationOnce(() => new Promise<void>(resolve => (finishFirstSend = resolve)))
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
})
