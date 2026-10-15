import { describe, expect, it } from 'vite-plus/test'
import { isReactive } from 'vue'
import type { UploadFile } from '@/services/uploadService'
import { UploadTracker } from '@/services/uploadTracker'

const makeFile = (overrides: Partial<UploadFile> = {}): UploadFile => ({
  id: crypto.randomUUID(),
  file: new File([new Uint8Array(1000)], 'song.mp3'),
  status: 'Ready',
  name: 'song.mp3',
  progress: 0,
  ...overrides,
})

describe('uploadTracker', () => {
  it('keeps the counts in step with status changes and removals', () => {
    const tracker = new UploadTracker()
    const first = makeFile()
    const second = makeFile()
    const third = makeFile({ status: 'Skipped' })

    tracker.add([first, second, third])
    tracker.update(first, { status: 'Uploading' })
    tracker.update(first, { status: 'Errored' })
    tracker.update(second, { status: 'Uploading' })
    tracker.update(second, { progress: 40 })

    expect(tracker.count(['Ready'])).toBe(0)
    expect(tracker.count(['Uploading'])).toBe(1)
    expect(tracker.count(['Errored', 'Skipped'])).toBe(2)
    expect(tracker.size).toBe(3)

    tracker.update(first, { status: 'Ready' })
    tracker.remove(third)

    expect(tracker.count(['Ready'])).toBe(1)
    expect(tracker.count(['Errored', 'Skipped'])).toBe(0)
    expect(tracker.size).toBe(2)
  })

  it('keeps the batch and sent bytes in step with progress, status changes and removals', () => {
    const tracker = new UploadTracker()
    const uploading = makeFile({ status: 'Uploading', progress: 50 })
    const processing = makeFile({ status: 'Processing' })
    const ready = makeFile()

    tracker.add([uploading, processing, ready, makeFile({ status: 'Skipped' })])

    expect(tracker.totalBatchBytes).toBe(3000)
    expect(tracker.totalSentBytes).toBe(1500)

    tracker.update(uploading, { progress: 80 })

    expect(tracker.totalSentBytes).toBe(1800)

    tracker.update(uploading, { status: 'Errored' })

    expect(tracker.totalBatchBytes).toBe(2000)
    expect(tracker.totalSentBytes).toBe(1000)

    tracker.update(uploading, { status: 'Ready', progress: 0 })
    tracker.remove(processing)

    expect(tracker.totalBatchBytes).toBe(2000)
    expect(tracker.totalSentBytes).toBe(0)
  })

  it('lists the files with given statuses in queue order', () => {
    const tracker = new UploadTracker()
    const files = [makeFile({ status: 'Errored' }), makeFile(), makeFile({ status: 'Uploaded' }), makeFile()]

    tracker.add(files)
    tracker.update(files[0], { status: 'Ready' })
    tracker.remove(files[3])

    const listed = tracker.getFilesWithStatus(['Ready', 'Uploaded'])

    expect(listed.map(({ id }) => id)).toEqual([files[0].id, files[1].id, files[2].id])
    expect(listed.every(file => isReactive(file))).toBe(true)
  })

  it('keeps a listed group in queue order as files join and leave it one by one', () => {
    const tracker = new UploadTracker()
    const files = [makeFile(), makeFile({ status: 'Errored' }), makeFile(), makeFile({ status: 'Uploaded' })]

    tracker.add(files)

    const listedBefore = tracker.getFilesWithStatus(['Ready', 'Uploaded'])

    tracker.update(files[1], { status: 'Ready' })
    tracker.remove(files[3])
    tracker.update(files[0], { status: 'Errored' })

    const listedAfter = tracker.getFilesWithStatus(['Ready', 'Uploaded'])

    expect(listedAfter.map(({ id }) => id)).toEqual([files[1].id, files[2].id])
    expect(listedBefore.map(({ id }) => id)).toEqual([files[0].id, files[2].id, files[3].id])
  })

  it('lists the files that changed in bulk', () => {
    const tracker = new UploadTracker()
    const files = [makeFile({ status: 'Errored' }), makeFile(), makeFile({ status: 'Canceled' })]

    tracker.add(files)
    tracker.getFilesWithStatus(['Ready'])
    tracker.updateMany([files[0], files[2]], { status: 'Ready' })

    expect(tracker.getFilesWithStatus(['Ready']).map(({ id }) => id)).toEqual(files.map(({ id }) => id))
    expect(tracker.getFilesWithStatus(['Errored', 'Canceled'])).toEqual([])
  })

  it('offers the earliest queued ready file, including one that became ready again', () => {
    const tracker = new UploadTracker()
    const files = [makeFile(), makeFile(), makeFile(), makeFile()]

    tracker.add(files)
    tracker.update(files[0], { status: 'Uploading' })
    tracker.update(files[1], { status: 'Uploading' })

    expect(tracker.getFirstReadyFile()?.id).toBe(files[2].id)

    tracker.update(files[1], { status: 'Ready' })

    expect(tracker.getFirstReadyFile()?.id).toBe(files[1].id)

    tracker.remove(files[1])
    tracker.remove(files[2])

    expect(tracker.getFirstReadyFile()?.id).toBe(files[3].id)

    tracker.update(files[3], { status: 'Uploading' })

    expect(tracker.getFirstReadyFile()).toBeUndefined()
  })

  it('finds a file by its current upload key', () => {
    const tracker = new UploadTracker()
    const file = makeFile()

    tracker.add([file])
    tracker.update(file, { uploadKey: 'first-key' })
    tracker.update(file, { uploadKey: 'second-key' })

    expect(tracker.findByUploadKey('first-key')).toBeUndefined()
    expect(tracker.findByUploadKey('second-key')?.id).toBe(file.id)

    tracker.remove(file)

    expect(tracker.findByUploadKey('second-key')).toBeUndefined()
  })

  it('changes a file it does not track without counting it', () => {
    const tracker = new UploadTracker()
    const file = makeFile()

    tracker.update(file, { status: 'Uploading', progress: 30 })

    expect(file.status).toBe('Uploading')
    expect(file.progress).toBe(30)
    expect(tracker.size).toBe(0)
    expect(tracker.totalSentBytes).toBe(0)
  })
})
