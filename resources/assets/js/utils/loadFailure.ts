export type LoadFailureCause = 'offline' | 'outdated' | 'unknown'

const findEntryScriptUrl = () => document.querySelector<HTMLScriptElement>('script[type="module"][src]')?.src

/**
 * A deploy replaces the build, so the entry script this page was served with is gone (404)
 * once a newer version of Koel is live.
 */
export const detectLoadFailureCause = async (): Promise<LoadFailureCause> => {
  if (!navigator.onLine) {
    return 'offline'
  }

  const entryScriptUrl = findEntryScriptUrl()

  if (!entryScriptUrl) {
    return 'unknown'
  }

  try {
    const response = await fetch(entryScriptUrl, { cache: 'no-store' })
    await response.body?.cancel()

    return response.status === 404 ? 'outdated' : 'unknown'
  } catch {
    return 'unknown'
  }
}
