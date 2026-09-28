import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import {
  getExtensionFromResponse,
  isArchiveCreatedBefore,
  makeEntryBaseName,
  makeUniqueName,
  toSafeFileName,
  ZipInProgressError,
  ZipTooLargeError,
  ZipUnsupportedError,
  zipDownloadService,
} from './zipDownloadService'

const addedEntries: string[] = []

vi.mock('@zip.js/zip.js', () => ({
  configure: vi.fn(),
  ZipWriter: class {
    constructor(private readonly writable: WritableStream) {}

    async add(name: string, stream: ReadableStream) {
      addedEntries.push(name)
      await new Response(stream).arrayBuffer()
    }

    async close() {
      await this.writable.close()
    }
  },
}))

describe('zipDownloadService', () => {
  const h = createHarness({
    beforeEach: () => {
      addedEntries.length = 0
      zipDownloadService.dismiss()
    },
  })

  const makeSong = (overrides: Partial<Song> = {}) =>
    h.factory('song').make({ artist_name: 'Dio', title: 'Holy Diver', track: 3, file_size: 10, ...overrides })

  const directory = {
    keys: async function* () {},
    removeEntry: vi.fn().mockResolvedValue(undefined),
    getFileHandle: vi.fn(),
  }

  const stubPrivateStorage = () => {
    const handle = {
      createWritable: vi.fn().mockResolvedValue(new WritableStream()),
      getFile: vi.fn().mockResolvedValue(new File(['zip'], 'download.zip')),
    }

    vi.stubGlobal('navigator', {
      storage: {
        estimate: vi.fn().mockResolvedValue({ quota: 10_000, usage: 0 }),
        getDirectory: vi.fn().mockResolvedValue(
          Object.assign(directory, {
            getFileHandle: vi.fn().mockResolvedValue(handle),
          }),
        ),
      },
    })
  }

  const stubSongDownloads = () =>
    vi.stubGlobal(
      'fetch',
      vi.fn().mockImplementation(async () => {
        const response = new Response('audio', { headers: { 'content-disposition': 'attachment; filename="x.mp3"' } })
        Object.defineProperty(response, 'url', { value: 'http://localhost/download/songs' })

        return response
      }),
    )

  beforeEach(() => {
    URL.createObjectURL = vi.fn().mockReturnValue('blob:zip')
    URL.revokeObjectURL = vi.fn()
  })

  afterEach(() => vi.unstubAllGlobals())

  it.each<[string, string]>([
    ['AC/DC: Live?', 'AC-DC- Live-'],
    ['  Plain  ', 'Plain'],
    ['', 'Untitled'],
  ])('makes %s safe as a file name', (name, safeName) => {
    expect(toSafeFileName(name)).toBe(safeName)
  })

  it('names an entry by position, by track, or by artist and title alone', () => {
    const song = makeSong()

    expect(makeEntryBaseName(song, 7, 120, 'position')).toBe('007 Dio - Holy Diver')
    expect(makeEntryBaseName(song, 7, 9, 'position')).toBe('07 Dio - Holy Diver')
    expect(makeEntryBaseName(song, 7, 9, 'track')).toBe('03 Dio - Holy Diver')
    expect(makeEntryBaseName(song, 7, 9, 'none')).toBe('Dio - Holy Diver')
    expect(makeEntryBaseName(makeSong({ track: null }), 7, 9, 'track')).toBe('Dio - Holy Diver')
  })

  it('numbers a name that is already taken, skipping numbered names in use', () => {
    const usedNames = new Set<string>()

    expect(makeUniqueName('Song.mp3', usedNames)).toBe('Song.mp3')
    expect(makeUniqueName('Song.mp3', usedNames)).toBe('Song (2).mp3')
    expect(makeUniqueName('Song (2).mp3', usedNames)).toBe('Song (2) (2).mp3')
    expect(makeUniqueName('Song.mp3', usedNames)).toBe('Song (3).mp3')
  })

  it('reads the extension from the download or its final URL', () => {
    const fromDisposition = new Response(null, { headers: { 'content-disposition': 'attachment; filename="a.FLAC"' } })
    const fromUrl = new Response(null)
    Object.defineProperty(fromUrl, 'url', { value: 'https://bucket.example/key__song.m4a?X-Amz-Signature=abc' })

    expect(getExtensionFromResponse(fromDisposition)).toBe('.flac')
    expect(getExtensionFromResponse(fromUrl)).toBe('.m4a')
  })

  it('refuses songs that need more space than the browser allows', async () => {
    stubPrivateStorage()

    await expect(
      zipDownloadService.start([makeSong({ file_size: 9_000 }), makeSong({ file_size: 2_000 })], 'Big', 'none'),
    ).rejects.toThrow(ZipTooLargeError)
  })

  it('starts when the browser cannot estimate its storage', async () => {
    stubPrivateStorage()
    stubSongDownloads()
    navigator.storage.estimate = vi.fn().mockRejectedValue(new Error('No estimate'))
    h.mock(zipDownloadService, 'save')

    await zipDownloadService.start([makeSong()], 'Songs', 'none')
    await zipDownloadService.building

    expect(addedEntries).toHaveLength(1)
  })

  it('refuses when the browser has no private storage', async () => {
    vi.stubGlobal('navigator', {})

    await expect(zipDownloadService.start([makeSong()], 'Songs', 'none')).rejects.toThrow(ZipUnsupportedError)
  })

  it('refuses a second archive while one is being prepared', async () => {
    zipDownloadService.state.status = 'zipping'

    await expect(zipDownloadService.start([makeSong()], 'Songs', 'none')).rejects.toThrow(ZipInProgressError)
  })

  it('zips songs and episodes in order and saves the archive', async () => {
    stubPrivateStorage()
    stubSongDownloads()
    const episode = h.factory('episode').make({ podcast_title: 'The Show', title: 'Pilot' })
    const playables = [makeSong({ title: 'One' }), episode, makeSong({ title: 'Two' })]
    const saveMock = h.mock(zipDownloadService, 'save')

    await zipDownloadService.start(playables, 'My Mix', 'position')
    await zipDownloadService.building

    expect(addedEntries).toEqual(['01 Dio - One.mp3', '02 The Show - Pilot.mp3', '03 Dio - Two.mp3'])
    expect(saveMock).toHaveBeenCalled()
    expect(zipDownloadService.state.archiveName).toBe('My Mix.zip')
    expect(zipDownloadService.state.bytesDone).toBe(15)
  })

  it('fails with a message when a song cannot be downloaded', async () => {
    stubPrivateStorage()
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(null, { status: 404 })))

    await zipDownloadService.start([makeSong()], 'Songs', 'none')
    await zipDownloadService.building

    expect(zipDownloadService.state.status).toBe('failed')
    expect(zipDownloadService.state.error).not.toBe('')
  })

  it('deletes the archive file of a failed download', async () => {
    stubPrivateStorage()
    directory.removeEntry.mockClear()
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(null, { status: 500 })))

    await zipDownloadService.start([makeSong()], 'Songs', 'none')
    await zipDownloadService.building

    expect(directory.removeEntry).toHaveBeenCalledWith(expect.stringMatching(/^download-\d+-[\w-]+\.zip$/))
  })

  it('does not save an archive cancelled while it was being finished', async () => {
    stubPrivateStorage()
    stubSongDownloads()
    const saveMock = h.mock(zipDownloadService, 'save')
    const addedLastSong = new Promise<void>(resolve => {
      const originalFetch = globalThis.fetch
      vi.stubGlobal('fetch', async (...args: Parameters<typeof fetch>) => {
        const response = await originalFetch(...args)
        resolve()
        return response
      })
    })

    await zipDownloadService.start([makeSong()], 'Songs', 'none')
    await addedLastSong
    zipDownloadService.cancel()
    await zipDownloadService.building

    expect(saveMock).not.toHaveBeenCalled()
    expect(zipDownloadService.state.status).toBe('idle')
  })

  it('treats only archives older than the cutoff as leftovers', () => {
    expect(isArchiveCreatedBefore('download-1000-abc.zip', 2000)).toBe(true)
    expect(isArchiveCreatedBefore('download-3000-abc.zip', 2000)).toBe(false)
    expect(isArchiveCreatedBefore('something-else.zip', 2000)).toBe(false)
  })

  it('fails instead of hanging when private storage cannot be opened', async () => {
    vi.stubGlobal('navigator', { storage: { getDirectory: vi.fn().mockRejectedValue(new Error('No storage')) } })

    await zipDownloadService.start([makeSong()], 'Songs', 'none')
    await zipDownloadService.building

    expect(zipDownloadService.state.status).toBe('failed')
  })
})
