import { computed, onBeforeUnmount, ref, toRef } from 'vue'
import type { UploadFile, UploadStatus } from '@/services/uploadService'
import { uploadService } from '@/services/uploadService'

const SAMPLE_INTERVAL_MS = 1000
const SPEED_SMOOTHING = 0.3

const FULLY_SENT_STATUSES: UploadStatus[] = ['Processing', 'Uploaded']
const IN_PROGRESS_STATUSES: UploadStatus[] = ['Ready', 'Uploading', 'Retrying', 'Processing']
const OUTSIDE_BATCH_STATUSES: UploadStatus[] = ['Skipped', 'Canceled', 'Errored']

const countWithStatus = (files: UploadFile[], statuses: UploadStatus[]) =>
  files.filter(({ status }) => statuses.includes(status)).length

const getSentBytes = (file: UploadFile) => {
  if (FULLY_SENT_STATUSES.includes(file.status)) {
    return file.file.size
  }

  return file.status === 'Uploading' ? (file.file.size * file.progress) / 100 : 0
}

export const useUploadProgress = () => {
  const files = toRef(uploadService.state, 'files')

  const batch = computed(() => files.value.filter(({ status }) => !OUTSIDE_BATCH_STATUSES.includes(status)))
  const totalBytes = computed(() => batch.value.reduce((total, file) => total + file.file.size, 0))
  const sentBytes = computed(() => batch.value.reduce((total, file) => total + getSentBytes(file), 0))

  const hasFilesInProgress = computed(() => countWithStatus(files.value, IN_PROGRESS_STATUSES) > 0)

  const bytesPerSecond = ref(0)
  let lastSample = { bytes: sentBytes.value, at: Date.now() }

  const sampleSpeed = () => {
    const now = Date.now()
    const sentSinceLastSample = sentBytes.value - lastSample.bytes

    if (sentSinceLastSample >= 0 && now > lastSample.at) {
      const currentSpeed = (sentSinceLastSample * 1000) / (now - lastSample.at)

      bytesPerSecond.value = bytesPerSecond.value
        ? SPEED_SMOOTHING * currentSpeed + (1 - SPEED_SMOOTHING) * bytesPerSecond.value
        : currentSpeed
    }

    lastSample = { bytes: sentBytes.value, at: now }
  }

  const sampler = window.setInterval(sampleSpeed, SAMPLE_INTERVAL_MS)
  onBeforeUnmount(() => window.clearInterval(sampler))

  const secondsLeft = computed(() => {
    if (!hasFilesInProgress.value || bytesPerSecond.value <= 0) {
      return null
    }

    return (totalBytes.value - sentBytes.value) / bytesPerSecond.value
  })

  return { totalBytes, sentBytes, secondsLeft }
}
