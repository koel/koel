import type { ZipWriter } from '@zip.js/zip.js'
import { reactive } from 'vue'
import { authService } from '@/services/authService'
import { eventBus } from '@/utils/eventBus'

export class ZipTooLargeError extends Error {}
export class ZipUnsupportedError extends Error {}
export class ZipInProgressError extends Error {}

export type ZipEntryNumbering = 'position' | 'track' | 'none'

type ZipStatus = 'idle' | 'zipping' | 'ready' | 'failed'

const ARCHIVE_FILE_PREFIX = 'download-'
const ARCHIVE_RETENTION_MS = 5 * 60_000
const LEFTOVER_ARCHIVE_AGE_MS = 24 * 60 * 60_000
const UNSAFE_FILE_NAME_CHARACTERS = '\\/:*?"<>|'
const FIRST_PRINTABLE_CHARACTER_CODE = 32

const isUnsafeInFileName = (character: string) =>
  character.charCodeAt(0) < FIRST_PRINTABLE_CHARACTER_CODE || UNSAFE_FILE_NAME_CHARACTERS.includes(character)

export const toSafeFileName = (name: string) =>
  Array.from(name, character => (isUnsafeInFileName(character) ? '-' : character))
    .join('')
    .trim() || 'Untitled'

const padNumber = (value: number, width: number) => String(value).padStart(width, '0')

const getCreditedTitle = (playable: Playable) => {
  const credit = playable.type === 'songs' ? playable.artist_name : playable.podcast_title

  return credit ? `${credit} - ${playable.title}` : playable.title
}

export const makeEntryBaseName = (
  playable: Playable,
  position: number,
  count: number,
  numbering: ZipEntryNumbering,
) => {
  const title = getCreditedTitle(playable)

  if (numbering === 'position') {
    return `${padNumber(position, Math.max(2, String(count).length))} ${title}`
  }

  if (numbering === 'track' && playable.type === 'songs' && playable.track) {
    return `${padNumber(playable.track, 2)} ${title}`
  }

  return title
}

const withCopyNumber = (name: string, copyNumber: number) => {
  const extensionStart = name.lastIndexOf('.')

  return extensionStart > 0
    ? `${name.slice(0, extensionStart)} (${copyNumber})${name.slice(extensionStart)}`
    : `${name} (${copyNumber})`
}

export const makeUniqueName = (name: string, usedNames: Set<string>) => {
  let uniqueName = name

  for (let copyNumber = 2; usedNames.has(uniqueName); copyNumber++) {
    uniqueName = withCopyNumber(name, copyNumber)
  }

  usedNames.add(uniqueName)

  return uniqueName
}

export const getExtensionFromResponse = (response: Response) => {
  const disposition = response.headers.get('content-disposition') ?? ''
  const nameInDisposition = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(disposition)?.[1]
  const name = nameInDisposition ? decodeURIComponent(nameInDisposition) : new URL(response.url).pathname
  const extension = /\.([a-z0-9]{1,5})$/i.exec(name)?.[1]

  return extension ? `.${extension.toLowerCase()}` : ''
}

export const isArchiveCreatedBefore = (fileName: string, time: number) => {
  const createdAt = Number(/^download-(\d+)-/.exec(fileName)?.[1])

  return Number.isFinite(createdAt) && createdAt < time
}

const getFileSize = (playable: Playable) => (playable.type === 'songs' ? (playable.file_size ?? 0) : 0)

export const getTotalBytes = (playables: Playable[]) =>
  playables.reduce((total, playable) => total + getFileSize(playable), 0)

const getDownloadUrl = (playable: Playable) =>
  `${window.KOEL.base_url}download/songs?songs[]=${playable.id}&t=${authService.getAudioToken()}`

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
    error: '',
    fileUrl: null as string | null,
  }),

  abortController: null as AbortController | null,
  building: Promise.resolve(),

  isSupported: () => typeof navigator.storage?.getDirectory === 'function',

  /**
   * Checks the items, then builds the archive in the background; `building` settles when it is done.
   *
   * @throws {ZipTooLargeError} when the items need more space than the browser allows the site
   * @throws {ZipUnsupportedError} when the browser can't write the archive to its private storage
   * @throws {ZipInProgressError} when another archive is still being built
   */
  async start(playables: Playable[], archiveName: string, numbering: ZipEntryNumbering) {
    if (this.state.status === 'zipping') {
      throw new ZipInProgressError('Another download is already in progress.')
    }

    if (!this.isSupported()) {
      throw new ZipUnsupportedError('This browser can’t download these at once.')
    }

    if (getTotalBytes(playables) > (await this.getAvailableStorageBytes())) {
      throw new ZipTooLargeError('Download too large for this browser.')
    }

    this.dismiss()

    Object.assign(this.state, {
      status: 'zipping',
      archiveName: `${toSafeFileName(archiveName)}.zip`,
      bytesDone: 0,
      bytesTotal: getTotalBytes(playables),
      error: '',
    })

    this.abortController = new AbortController()
    this.building = this.build(playables, numbering, this.abortController.signal)
  },

  async build(playables: Playable[], numbering: ZipEntryNumbering, signal: AbortSignal) {
    const archiveFileName = `${ARCHIVE_FILE_PREFIX}${Date.now()}-${crypto.randomUUID()}.zip`
    const writeAborter = new AbortController()
    let directory: FileSystemDirectoryHandle | null = null
    let writing: Promise<void> = Promise.resolve()

    try {
      directory = await navigator.storage.getDirectory()
      await this.removeLeftoverArchives(directory)

      const { ZipWriter, configure } = await import('@zip.js/zip.js')
      configure({ useWebWorkers: false })

      const handle = await directory.getFileHandle(archiveFileName, { create: true })
      const { readable, writable } = new TransformStream<Uint8Array, Uint8Array>()
      writing = readable.pipeTo(await handle.createWritable(), { signal: writeAborter.signal })

      const zipWriter = new ZipWriter(writable, {
        zip64: true,
        dataDescriptor: true,
      })

      const usedNames = new Set<string>()

      for (const [index, playable] of playables.entries()) {
        const baseName = toSafeFileName(makeEntryBaseName(playable, index + 1, playables.length, numbering))
        await this.addPlayable(zipWriter, playable, baseName, usedNames, signal)
      }

      await zipWriter.close()
      await writing
      signal.throwIfAborted()

      this.state.fileUrl = URL.createObjectURL(await handle.getFile())
      this.state.status = 'ready'
      const archiveDirectory = directory
      this.save(() => this.removeArchive(archiveDirectory, archiveFileName))
    } catch (error: unknown) {
      writeAborter.abort()
      await writing.catch(() => {})
      await directory?.removeEntry(archiveFileName).catch(() => {})

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

  async removeLeftoverArchives(directory: FileSystemDirectoryHandle) {
    const oldestKeptTime = Date.now() - LEFTOVER_ARCHIVE_AGE_MS

    for await (const name of directory.keys()) {
      if (isArchiveCreatedBefore(name, oldestKeptTime)) {
        await directory.removeEntry(name).catch(() => {})
      }
    }
  },

  async removeArchive(directory: FileSystemDirectoryHandle, archiveFileName: string) {
    await directory.removeEntry(archiveFileName).catch(() => {})
  },

  async addPlayable(
    zipWriter: ZipWriter<unknown>,
    playable: Playable,
    baseName: string,
    usedNames: Set<string>,
    signal: AbortSignal,
  ) {
    try {
      const response = await fetch(getDownloadUrl(playable), { signal })

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
      throw new Error(`“${playable.title}” could not be downloaded (${reason}).`, { cause: error })
    }
  },

  async getAvailableStorageBytes() {
    const { quota, usage = 0 } = (await navigator.storage.estimate?.().catch(() => undefined)) ?? {}

    return quota === undefined ? Number.POSITIVE_INFINITY : quota - usage
  },

  save(releaseArchive: Closure = () => {}) {
    if (!this.state.fileUrl) {
      return
    }

    const fileUrl = this.state.fileUrl
    Object.assign(document.createElement('a'), { href: fileUrl, download: this.state.archiveName }).click()

    this.state.fileUrl = null
    this.state.status = 'idle'
    eventBus.emit('DOWNLOAD_ARCHIVE_SAVED')

    window.setTimeout(() => {
      URL.revokeObjectURL(fileUrl)
      releaseArchive()
    }, ARCHIVE_RETENTION_MS)
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
    })
  },
}
