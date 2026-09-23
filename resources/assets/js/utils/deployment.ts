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
    const response = await fetch(entryScriptUrl, { cache: 'no-store' })
    await response.body?.cancel()

    return response.status === 404
  } catch {
    return false
  }
}
