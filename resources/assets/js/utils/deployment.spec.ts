import { afterEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { isNewerVersionDeployed } from './deployment'

describe('deployment', () => {
  createHarness()

  const addEntryScript = () => {
    const script = document.createElement('script')
    script.type = 'module'
    script.src = 'https://koel.test/build/assets/app-abc123.js'
    document.head.appendChild(script)
  }

  afterEach(() => {
    document.head.querySelectorAll('script[type="module"]').forEach(script => script.remove())
    vi.unstubAllGlobals()
  })

  it('says a newer version is deployed when the entry script is gone', async () => {
    addEntryScript()
    const fetchMock = vi.fn().mockResolvedValue(new Response(null, { status: 404 }))
    vi.stubGlobal('fetch', fetchMock)

    expect(await isNewerVersionDeployed()).toBe(true)
    expect(fetchMock).toHaveBeenCalledWith(
      'https://koel.test/build/assets/app-abc123.js',
      expect.objectContaining({ cache: 'no-store', signal: expect.any(AbortSignal) }),
    )
  })

  it('says no when the entry script is still there', async () => {
    addEntryScript()
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('', { status: 200 })))

    expect(await isNewerVersionDeployed()).toBe(false)
  })

  it('says no when the server errors', async () => {
    addEntryScript()
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(null, { status: 503 })))

    expect(await isNewerVersionDeployed()).toBe(false)
  })

  it('says no when the check itself fails', async () => {
    addEntryScript()
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')))

    expect(await isNewerVersionDeployed()).toBe(false)
  })

  it('says no when the check times out', async () => {
    addEntryScript()
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new DOMException('Timed out', 'TimeoutError')))

    expect(await isNewerVersionDeployed()).toBe(false)
  })

  it('says no when there is no entry script to check', async () => {
    const fetchMock = vi.fn()
    vi.stubGlobal('fetch', fetchMock)

    expect(await isNewerVersionDeployed()).toBe(false)
    expect(fetchMock).not.toHaveBeenCalled()
  })
})
