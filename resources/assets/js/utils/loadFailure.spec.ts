import { afterEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { detectLoadFailureCause } from './loadFailure'

describe('loadFailure', () => {
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
    vi.restoreAllMocks()
  })

  it('reports offline when the browser has no connection', async () => {
    vi.spyOn(navigator, 'onLine', 'get').mockReturnValue(false)
    const fetchMock = vi.fn()
    vi.stubGlobal('fetch', fetchMock)

    expect(await detectLoadFailureCause()).toBe('offline')
    expect(fetchMock).not.toHaveBeenCalled()
  })

  it('reports outdated when the entry script is gone', async () => {
    addEntryScript()
    const fetchMock = vi.fn().mockResolvedValue(new Response(null, { status: 404 }))
    vi.stubGlobal('fetch', fetchMock)

    expect(await detectLoadFailureCause()).toBe('outdated')
    expect(fetchMock).toHaveBeenCalledWith('https://koel.test/build/assets/app-abc123.js', { cache: 'no-store' })
  })

  it('reports unknown when the entry script is still there', async () => {
    addEntryScript()
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('', { status: 200 })))

    expect(await detectLoadFailureCause()).toBe('unknown')
  })

  it('reports unknown when the server errors', async () => {
    addEntryScript()
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(null, { status: 503 })))

    expect(await detectLoadFailureCause()).toBe('unknown')
  })

  it('reports unknown when the check itself fails', async () => {
    addEntryScript()
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')))

    expect(await detectLoadFailureCause()).toBe('unknown')
  })

  it('reports unknown when there is no entry script to check', async () => {
    const fetchMock = vi.fn()
    vi.stubGlobal('fetch', fetchMock)

    expect(await detectLoadFailureCause()).toBe('unknown')
    expect(fetchMock).not.toHaveBeenCalled()
  })
})
