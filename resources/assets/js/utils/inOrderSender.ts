import { logger } from '@/utils/logger'

/**
 * Wrap a save so its calls never overlap: while one is in flight, only the newest value waiting is sent after it.
 * A retried request can then never land after, and overwrite, a newer one.
 */
export const createInOrderSender = <T>(send: (value: T) => Promise<unknown>) => {
  let running: Promise<void> | null = null
  let waiting: { value: T } | null = null

  const sendWaitingValues = async () => {
    while (waiting) {
      const { value } = waiting
      waiting = null

      try {
        await send(value)
      } catch (error: unknown) {
        logger.error(error)
      }
    }

    running = null
  }

  return (value: T) => {
    waiting = { value }
    running ??= sendWaitingValues()

    return running
  }
}
