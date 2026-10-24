import { afterEach, describe, expect, it, vi } from 'vite-plus/test'

const createFakeCache = (urls: string[]) => {
  const entries = new Map(urls.map(url => [url, new Response('cached')]))

  return {
    entries,
    match: vi.fn(async (request: Request | string) => entries.get(typeof request === 'string' ? request : request.url)),
    put: vi.fn(async (request: Request, response: Response) => void entries.set(request.url, response)),
    keys: vi.fn(async () => [...entries.keys()].map(url => new Request(url))),
    delete: vi.fn(async (request: Request) => entries.delete(request.url)),
  }
}

const loadFetchListener = async () => {
  let fetchListener: (event: Partial<FetchEvent>) => void = () => {}

  vi.spyOn(self, 'addEventListener').mockImplementation((type: string, listener: any) => {
    if (type === 'fetch') {
      fetchListener = listener
    }
  })

  vi.resetModules()
  await import('./service-worker')

  return fetchListener
}

const fetchThroughWorker = async (fetchListener: (event: Partial<FetchEvent>) => void, url: string) => {
  let responsePromise: Promise<Response> | undefined

  fetchListener({
    request: new Request(url),
    respondWith: (response: Promise<Response>) => void (responsePromise = response),
  })

  await responsePromise
}

describe('service worker', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    vi.restoreAllMocks()
  })

  it('evicts the oldest images once the image cache is full', async () => {
    const imageCache = createFakeCache(Array.from({ length: 500 }, (_, i) => `https://koel.test/img/${i}.jpg`))
    const staticCache = createFakeCache([])

    vi.stubGlobal('caches', {
      open: vi.fn(async (name: string) => (name === 'koel-images-v1' ? imageCache : staticCache)),
    })
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => new Response('image')),
    )

    const fetchListener = await loadFetchListener()
    await fetchThroughWorker(fetchListener, 'https://koel.test/img/new.jpg')

    await vi.waitFor(() => expect(imageCache.entries.size).toBe(500))
    expect(imageCache.entries.has('https://koel.test/img/0.jpg')).toBe(false)
    expect(imageCache.entries.has('https://koel.test/img/new.jpg')).toBe(true)
    expect(staticCache.put).not.toHaveBeenCalled()
  })

  it.each(['avif', 'webp'])('caches %s covers in the image cache', async extension => {
    const imageCache = createFakeCache([])
    const staticCache = createFakeCache([])

    vi.stubGlobal('caches', {
      open: vi.fn(async (name: string) => (name === 'koel-images-v1' ? imageCache : staticCache)),
    })
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => new Response('image')),
    )

    const coverUrl = `https://koel.test/img/covers/cover.${extension}`
    const fetchListener = await loadFetchListener()
    await fetchThroughWorker(fetchListener, coverUrl)

    await vi.waitFor(() => expect(imageCache.entries.has(coverUrl)).toBe(true))
    expect(staticCache.put).not.toHaveBeenCalled()
  })

  it('serves cached audio for a signed play URL with lossless formats', async () => {
    const audioCache = createFakeCache(['https://koel.test/play/song-id'])

    vi.stubGlobal('caches', {
      open: vi.fn(async () => audioCache),
    })
    vi.stubGlobal('fetch', vi.fn())

    const fetchListener = await loadFetchListener()
    await fetchThroughWorker(
      fetchListener,
      'https://koel.test/play/song-id?lossless%5B0%5D=flac&lossless%5B1%5D=wav&user=user-id&expires=1&signature=sig',
    )

    expect(audioCache.match).toHaveBeenCalledWith('https://koel.test/play/song-id')
    expect(fetch).not.toHaveBeenCalled()
  })

  it('does not evict fonts or stylesheets', async () => {
    const imageCache = createFakeCache([])
    const staticCache = createFakeCache([])

    vi.stubGlobal('caches', {
      open: vi.fn(async (name: string) => (name === 'koel-images-v1' ? imageCache : staticCache)),
    })
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => new Response('font')),
    )

    const fetchListener = await loadFetchListener()
    await fetchThroughWorker(fetchListener, 'https://koel.test/fonts/font.woff2')

    await vi.waitFor(() => expect(staticCache.entries.has('https://koel.test/fonts/font.woff2')).toBe(true))
    expect(staticCache.keys).not.toHaveBeenCalled()
    expect(imageCache.put).not.toHaveBeenCalled()
  })
})
