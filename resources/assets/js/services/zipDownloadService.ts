import type { ZipWriter } from '@zip.js/zip.js'
import { reactive } from 'vue'
import { authService } from '@/services/authService'

export class ZipTooLargeError extends Error {}
export class ZipUnsupportedError extends Error {}
export class ZipInProgressError extends Error {}

export type ZipEntryNumbering = 'position' | 'track' | 'none'

type ZipStatus = 'idle' | 'zipping' | 'ready' | 'failed'

export const MAX_ZIP_BYTES = 4_000_000_000

const ARCHIVE_FILE_NAME = 'download.zip'
const OBJECT_URL_LIFETIME_MS = 60_000
const UNSAFE_FILE_NAME_CHARACTERS = '\\/:*?"<>|'
const FIRST_PRINTABLE_CHARACTER_CODE = 32

const isUnsafeInFileName = (character: string) =>
  character.charCodeAt(0) < FIRST_PRINTABLE_CHARACTER_CODE || UNSAFE_FILE_NAME_CHARACTERS.includes(character)

export const toSafeFileName = (name: string) =>
  Array.from(name, character => (isUnsafeInFileName(character) ? '-' : character))
    .join('')
    .trim() || 'Untitled'

const padNumber = (value: number, width: number) => String(value).padStart(width, '0')

export const makeEntryBaseName = (song: Song, position: number, count: number, numbering: ZipEntryNumbering) => {
  const title = song.artist_name ? `${song.artist_name} - ${song.title}` : song.title

  if (numbering === 'position') {
    return `${padNumber(position, Math.max(2, String(count).length))} ${title}`
  }

  if (numbering === 'track' && song.track) {
    return `${padNumber(song.track, 2)} ${title}`
  }

  return title
}

export const makeUniqueName = (name: string, usedNames: Map<string, number>) => {
  const timesUsed = usedNames.get(name) ?? 0
  usedNames.set(name, timesUsed + 1)

  if (!timesUsed) {
    return name
  }

  const extensionStart = name.lastIndexOf('.')

  return extensionStart > 0
    ? `${name.slice(0, extensionStart)} (${timesUsed + 1})${name.slice(extensionStart)}`
    : `${name} (${timesUsed + 1})`
}

export const getExtensionFromResponse = (response: Response) => {
  const disposition = response.headers.get('content-disposition') ?? ''
  const nameInDisposition = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(disposition)?.[1]
  const name = nameInDisposition ? decodeURIComponent(nameInDisposition) : new URL(response.url).pathname
  const extension = /\.([a-z0-9]{1,5})$/i.exec(name)?.[1]

  return extension ? `.${extension.toLowerCase()}` : ''
}

export const getTotalBytes = (songs: Song[]) => songs.reduce((total, song) => total + (song.file_size ?? 0), 0)

const getDownloadUrl = (song: Song) =>
  `${window.KOEL.base_url}download/songs?songs[]=${song.id}&t=${authService.getAudioToken()}`

const countBytesInto = (onBytes: (count: number) => void) =>
  new TransformStream<Uint8Array, Uint8Array>({
    transform(chunk, controller) {
      onBytes(chunk.byteLength)
      controller.enqueue(chunk)
    },
  })

export const zipDownloadService = {
  state: reactive({
    status: 'idle' as ZipStatus,
    archiveName: '',
    bytesDone: 0,
    bytesTotal: 0,
    songsDone: 0,
    songsTotal: 0,
    error: '',
    fileUrl: null as string | null,
  }),

  abortController: null as AbortController | null,
  building: Promise.resolve(),
  revokeTimer: null as number | null,

  isSupported: () => typeof navigator.storage?.getDirectory === 'function',

  /**
   * Checks the songs, then builds the archive in the background; `building` settles when it is done.
   *
   * @throws {ZipTooLargeError} when the songs add up to more than MAX_ZIP_BYTES
   * @throws {ZipUnsupportedError} when the browser can't write the archive to its private storage
   * @throws {ZipInProgressError} when another archive is still being built
   */
  start(playables: Playable[], archiveName: string, numbering: ZipEntryNumbering) {
    const songs = playables.filter((playable): playable is Song => playable.type === 'songs')

    if (this.state.status === 'zipping') {
      throw new ZipInProgressError('Another download is still being prepared.')
    }

    if (!this.isSupported()) {
      throw new ZipUnsupportedError('This browser can’t prepare downloads of several songs.')
    }

    if (getTotalBytes(songs) > MAX_ZIP_BYTES) {
      throw new ZipTooLargeError('These songs add up to more than 4 GB. Please download fewer at a time.')
    }

    this.dismiss()

    Object.assign(this.state, {
      status: 'zipping',
      archiveName: `${toSafeFileName(archiveName)}.zip`,
      bytesDone: 0,
      bytesTotal: getTotalBytes(songs),
      songsDone: 0,
      songsTotal: songs.length,
      error: '',
    })

    this.abortController = new AbortController()
    this.building = this.build(songs, numbering, this.abortController.signal)
  },

  async build(songs: Song[], numbering: ZipEntryNumbering, signal: AbortSignal) {
    try {
      const { ZipWriter, configure } = await import('@zip.js/zip.js')
      configure({ useWebWorkers: false })

      const directory = await navigator.storage.getDirectory()
      await directory.removeEntry(ARCHIVE_FILE_NAME).catch(() => {})
      const handle = await directory.getFileHandle(ARCHIVE_FILE_NAME, { create: true })

      const zipWriter = new ZipWriter(await handle.createWritable(), {
        zip64: songs.some(song => !song.file_size),
        bufferedWrite: true,
      })

      const usedNames = new Map<string, number>()

      for (const [index, song] of songs.entries()) {
        const baseName = toSafeFileName(makeEntryBaseName(song, index + 1, songs.length, numbering))
        await this.addSong(zipWriter, song, baseName, usedNames, signal)
        this.state.songsDone++
      }

      await zipWriter.close()

      this.state.fileUrl = URL.createObjectURL(await handle.getFile())
      this.state.status = 'ready'
      this.save()
    } catch (error: unknown) {
      if (signal.aborted) {
        this.dismiss()
        return
      }

      this.state.status = 'failed'
      this.state.error = error instanceof Error ? error.message : 'The download could not be prepared.'
    } finally {
      this.abortController = null
    }
  },

  async addSong(
    zipWriter: ZipWriter<unknown>,
    song: Song,
    baseName: string,
    usedNames: Map<string, number>,
    signal: AbortSignal,
  ) {
    try {
      const response = await fetch(getDownloadUrl(song), { signal })

      if (!response.ok || !response.body) {
        throw new Error(`the server answered ${response.status}`)
      }

      await zipWriter.add(
        makeUniqueName(baseName + getExtensionFromResponse(response), usedNames),
        response.body.pipeThrough(countBytesInto(count => (this.state.bytesDone += count))),
        { level: 0, signal },
      )
    } catch (error: unknown) {
      if (signal.aborted) {
        throw error
      }

      const reason = error instanceof Error ? error.message : String(error)
      throw new Error(`“${song.title}” could not be downloaded (${reason}).`, { cause: error })
    }
  },

  save() {
    if (!this.state.fileUrl) {
      return
    }

    const fileUrl = this.state.fileUrl
    Object.assign(document.createElement('a'), { href: fileUrl, download: this.state.archiveName }).click()

    this.state.fileUrl = null
    this.state.status = 'idle'
    this.revokeTimer = window.setTimeout(() => {
      URL.revokeObjectURL(fileUrl)
      this.revokeTimer = null
    }, OBJECT_URL_LIFETIME_MS)
  },

  cancel() {
    this.abortController?.abort()
  },

  dismiss() {
    if (this.state.fileUrl) {
      URL.revokeObjectURL(this.state.fileUrl)
    }

    Object.assign(this.state, {
      status: 'idle',
      fileUrl: null,
      error: '',
      bytesDone: 0,
      bytesTotal: 0,
      songsDone: 0,
      songsTotal: 0,
    })
  },
}
