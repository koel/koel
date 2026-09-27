import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import {
  getExtensionFromResponse,
  makeEntryBaseName,
  makeUniqueName,
  MAX_ZIP_BYTES,
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
    async add(name: string, stream: ReadableStream) {
      addedEntries.push(name)
      await new Response(stream).arrayBuffer()
    }

    async close() {}
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

  const stubPrivateStorage = () => {
    const handle = {
      createWritable: vi.fn().mockResolvedValue(new WritableStream()),
      getFile: vi.fn().mockResolvedValue(new File(['zip'], 'download.zip')),
    }

    vi.stubGlobal('navigator', {
      storage: {
        getDirectory: vi.fn().mockResolvedValue({
          removeEntry: vi.fn().mockResolvedValue(undefined),
          getFileHandle: vi.fn().mockResolvedValue(handle),
        }),
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

  it('numbers a name that is already taken', () => {
    const usedNames = new Map<string, number>()

    expect(makeUniqueName('Song.mp3', usedNames)).toBe('Song.mp3')
    expect(makeUniqueName('Song.mp3', usedNames)).toBe('Song (2).mp3')
    expect(makeUniqueName('Song.mp3', usedNames)).toBe('Song (3).mp3')
  })

  it('reads the extension from the download or its final URL', () => {
    const fromDisposition = new Response(null, { headers: { 'content-disposition': 'attachment; filename="a.FLAC"' } })
    const fromUrl = new Response(null)
    Object.defineProperty(fromUrl, 'url', { value: 'https://bucket.example/key__song.m4a?X-Amz-Signature=abc' })

    expect(getExtensionFromResponse(fromDisposition)).toBe('.flac')
    expect(getExtensionFromResponse(fromUrl)).toBe('.m4a')
  })

  it('refuses songs that add up to more than the cap', async () => {
    stubPrivateStorage()

    await expect(
      zipDownloadService.start([makeSong({ file_size: MAX_ZIP_BYTES }), makeSong()], 'Big', 'none'),
    ).rejects.toThrow(ZipTooLargeError)
  })

  it('refuses when the browser has no private storage', async () => {
    vi.stubGlobal('navigator', {})

    await expect(zipDownloadService.start([makeSong()], 'Songs', 'none')).rejects.toThrow(ZipUnsupportedError)
  })

  it('refuses a second archive while one is being prepared', async () => {
    zipDownloadService.state.status = 'zipping'

    await expect(zipDownloadService.start([makeSong()], 'Songs', 'none')).rejects.toThrow(ZipInProgressError)
  })

  it('zips the songs in order, leaves episodes out, and gets ready to save', async () => {
    stubPrivateStorage()
    stubSongDownloads()
    const songs = [makeSong({ title: 'One' }), h.factory('episode').make(), makeSong({ title: 'Two' })]

    await zipDownloadService.start(songs, 'My Mix', 'position')

    expect(addedEntries).toEqual(['01 Dio - One.mp3', '02 Dio - Two.mp3'])
    expect(zipDownloadService.state.status).toBe('ready')
    expect(zipDownloadService.state.archiveName).toBe('My Mix.zip')
    expect(zipDownloadService.state.bytesDone).toBe(10)
  })

  it('fails with a message when a song cannot be downloaded', async () => {
    stubPrivateStorage()
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(null, { status: 404 })))

    await zipDownloadService.start([makeSong()], 'Songs', 'none')

    expect(zipDownloadService.state.status).toBe('failed')
    expect(zipDownloadService.state.error).not.toBe('')
  })
})
