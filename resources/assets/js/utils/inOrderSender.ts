import { logger } from '@/utils/logger'

/**
 * Wrap a save so its calls never overlap: while one is in flight, only the newest value waiting is sent after it.
 * A retried request can then never land after, and overwrite, a newer one.
 * The returned promise tells whether the newest value was saved.
 */
export const createInOrderSender = <T>(send: (value: T) => Promise<unknown>) => {
  let running: Promise<boolean> | null = null
  let waiting: { value: T } | null = null

  const sendWaitingValues = async () => {
    let newestSaved = false

    while (waiting) {
      const { value } = waiting
      waiting = null

      try {
        await new Promise(resolve => resolve(send(value)))
        newestSaved = true
      } catch (error: unknown) {
        logger.error(error)
        newestSaved = false
      }
    }

    running = null

    return newestSaved
  }

  return (value: T) => {
    waiting = { value }
    running ??= sendWaitingValues()

    return running
  }
}
