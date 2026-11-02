import { eventBus } from '@/utils/eventBus'

const ENTRY_SCRIPT_CHECK_TIMEOUT_MS = 10_000

const findEntryScriptUrl = () => document.querySelector<HTMLScriptElement>('script[type="module"][src]')?.src

/**
 * A deploy replaces the build, so the entry script this page was served with is gone (404)
 * once a newer version of Koel is live.
 */
export const isNewerVersionDeployed = async () => {
  const entryScriptUrl = findEntryScriptUrl()

  if (!entryScriptUrl) {
    return false
  }

  try {
    const response = await fetch(entryScriptUrl, {
      cache: 'no-store',
      signal: AbortSignal.timeout(ENTRY_SCRIPT_CHECK_TIMEOUT_MS),
    })
    await response.body?.cancel()

    return response.status === 404
  } catch {
    return false
  }
}

export const announceIfBuildChanged = (build: string | null | undefined) => {
  if (build && window.KOEL.build && build !== window.KOEL.build) {
    eventBus.emit('NEW_VERSION_DEPLOYED')
  }
}
