import { computed, onBeforeUnmount, ref } from 'vue'
import type { UploadStatus } from '@/services/uploadService'
import { uploadService } from '@/services/uploadService'

const SAMPLE_INTERVAL_MS = 1000
const SPEED_SMOOTHING = 0.3

const IN_PROGRESS_STATUSES: UploadStatus[] = ['Ready', 'Uploading', 'Retrying', 'Processing']

export const useUploadProgress = () => {
  const totalBytes = computed(() => uploadService.tracker.totalBatchBytes)
  const sentBytes = computed(() => uploadService.tracker.totalSentBytes)
  const hasFilesInProgress = computed(() => uploadService.tracker.count(IN_PROGRESS_STATUSES) > 0)

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
