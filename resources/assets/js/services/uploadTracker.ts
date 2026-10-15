import type { Ref } from 'vue'
import { reactive, ref, shallowReactive, toRaw } from 'vue'
import type { UploadFile, UploadStatus } from '@/services/uploadService'

type UploadFileChanges = Partial<Omit<UploadFile, 'id' | 'file' | 'name'>>

interface StatusGroup {
  statuses: UploadStatus[]
  membershipVersion: Ref<number>
  listedVersion: number
  files: UploadFile[]
}

const FULLY_SENT_STATUSES: UploadStatus[] = ['Processing', 'Uploaded']
const OUTSIDE_BATCH_STATUSES: UploadStatus[] = ['Skipped', 'Canceled', 'Errored']

const countBatchBytes = (file: UploadFile) => (OUTSIDE_BATCH_STATUSES.includes(file.status) ? 0 : file.file.size)

const countSentBytes = (file: UploadFile) => {
  if (FULLY_SENT_STATUSES.includes(file.status)) {
    return file.file.size
  }

  return file.status === 'Uploading' ? (file.file.size * file.progress) / 100 : 0
}

export class UploadTracker {
  private readonly reactiveFiles = new Map<UploadFile, UploadFile>()
  private readonly filesInQueueOrder: Array<UploadFile | undefined> = []
  private readonly queuePositions = new WeakMap<UploadFile, number>()
  private readonly filesByUploadKey = new Map<string, UploadFile>()
  private readonly statusGroups = new Map<string, StatusGroup>()
  private firstReadyPosition = 0
  private isChangingInBulk = false

  private readonly counts = shallowReactive<Record<UploadStatus, number>>({
    Ready: 0,
    Uploading: 0,
    Retrying: 0,
    Processing: 0,
    Uploaded: 0,
    Canceled: 0,
    Errored: 0,
    Skipped: 0,
  })

  private readonly batchBytes = ref(0)
  private readonly sentBytes = ref(0)

  public get totalBatchBytes() {
    return this.batchBytes.value
  }

  public get totalSentBytes() {
    return this.sentBytes.value
  }

  public get size() {
    return Object.values(this.counts).reduce((total, count) => total + count, 0)
  }

  public add(files: UploadFile[]) {
    this.changeInBulk(() =>
      files.forEach(file => {
        const rawFile = toRaw(file)

        this.reactiveFiles.set(rawFile, reactive(rawFile))
        this.queuePositions.set(rawFile, this.filesInQueueOrder.push(rawFile) - 1)
        this.indexUploadKey(rawFile)
        this.adjustTotals(rawFile, 1)
        this.enterStatus(rawFile)
        this.changeGroupMembership(rawFile, null, rawFile.status)
      }),
    )
  }

  public remove(file: UploadFile) {
    const rawFile = toRaw(file)

    if (!this.reactiveFiles.delete(rawFile)) {
      return
    }

    this.filesInQueueOrder[this.queuePositions.get(rawFile)!] = undefined
    this.unindexUploadKey(rawFile)
    this.adjustTotals(rawFile, -1)
    this.counts[rawFile.status] -= 1
    this.changeGroupMembership(rawFile, rawFile.status, null)
  }

  public removeWithStatus(statuses: UploadStatus[]) {
    this.changeInBulk(() => this.getFilesWithStatus(statuses).forEach(file => this.remove(file)))
  }

  public updateMany(files: UploadFile[], changes: UploadFileChanges) {
    this.changeInBulk(() => files.forEach(file => this.update(file, changes)))
  }

  public update(file: UploadFile, changes: UploadFileChanges) {
    const rawFile = toRaw(file)

    if (!this.reactiveFiles.has(rawFile)) {
      Object.assign(reactive(rawFile), changes)
      return
    }

    const previousStatus = rawFile.status

    this.unindexUploadKey(rawFile)
    this.adjustTotals(rawFile, -1)

    Object.assign(reactive(rawFile), changes)

    this.indexUploadKey(rawFile)
    this.adjustTotals(rawFile, 1)

    if (rawFile.status !== previousStatus) {
      this.counts[previousStatus] -= 1
      this.enterStatus(rawFile)
      this.changeGroupMembership(rawFile, previousStatus, rawFile.status)
    }
  }

  public count(statuses: UploadStatus[]) {
    return statuses.reduce((total, status) => total + this.counts[status], 0)
  }

  public getFilesWithStatus(statuses: UploadStatus[]) {
    const group = this.getStatusGroup(statuses)

    if (group.listedVersion !== group.membershipVersion.value) {
      group.files = this.collectFilesWithStatus(statuses)
      group.listedVersion = group.membershipVersion.value
    }

    return group.files
  }

  public findByUploadKey(key: string) {
    const file = this.filesByUploadKey.get(key)

    return file ? reactive(file) : undefined
  }

  public getFirstReadyFile() {
    for (; this.firstReadyPosition < this.filesInQueueOrder.length; ++this.firstReadyPosition) {
      const file = this.filesInQueueOrder[this.firstReadyPosition]

      if (file?.status === 'Ready') {
        return this.reactiveFiles.get(file)
      }
    }

    return undefined
  }

  private collectFilesWithStatus(statuses: UploadStatus[]) {
    const matches: UploadFile[] = []

    this.reactiveFiles.forEach((reactiveFile, rawFile) => {
      if (statuses.includes(rawFile.status)) {
        matches.push(reactiveFile)
      }
    })

    return matches
  }

  private getStatusGroup(statuses: UploadStatus[]) {
    const key = statuses.join()

    if (!this.statusGroups.has(key)) {
      this.statusGroups.set(key, { statuses, membershipVersion: ref(0), listedVersion: -1, files: [] })
    }

    return this.statusGroups.get(key)!
  }

  private changeInBulk(changes: () => void) {
    this.isChangingInBulk = true

    try {
      changes()
    } finally {
      this.isChangingInBulk = false
    }
  }

  private changeGroupMembership(file: UploadFile, previousStatus: UploadStatus | null, status: UploadStatus | null) {
    this.statusGroups.forEach(group => {
      const isInGroup = (candidate: UploadStatus | null) => candidate !== null && group.statuses.includes(candidate)
      const isJoining = isInGroup(status)

      if (isInGroup(previousStatus) === isJoining) {
        return
      }

      if (!this.isChangingInBulk && group.listedVersion === group.membershipVersion.value) {
        group.files = isJoining ? this.insertIntoList(group.files, file) : this.removeFromList(group.files, file)
        group.listedVersion += 1
      }

      group.membershipVersion.value += 1
    })
  }

  private findListIndex(list: UploadFile[], file: UploadFile) {
    const position = this.queuePositions.get(file)!
    let low = 0
    let high = list.length

    while (low < high) {
      const middle = (low + high) >> 1

      if (this.queuePositions.get(toRaw(list[middle]))! < position) {
        low = middle + 1
      } else {
        high = middle
      }
    }

    return low
  }

  private insertIntoList(list: UploadFile[], file: UploadFile) {
    const updatedList = list.slice()
    updatedList.splice(this.findListIndex(list, file), 0, reactive(file))

    return updatedList
  }

  private removeFromList(list: UploadFile[], file: UploadFile) {
    const updatedList = list.slice()
    updatedList.splice(this.findListIndex(list, file), 1)

    return updatedList
  }

  private enterStatus(file: UploadFile) {
    this.counts[file.status] += 1

    if (file.status === 'Ready') {
      this.firstReadyPosition = Math.min(this.firstReadyPosition, this.queuePositions.get(file)!)
    }
  }

  private adjustTotals(file: UploadFile, direction: 1 | -1) {
    this.batchBytes.value += direction * countBatchBytes(file)
    this.sentBytes.value += direction * countSentBytes(file)
  }

  private indexUploadKey(file: UploadFile) {
    file.uploadKey && this.filesByUploadKey.set(file.uploadKey, file)
  }

  private unindexUploadKey(file: UploadFile) {
    file.uploadKey && this.filesByUploadKey.delete(file.uploadKey)
  }
}
