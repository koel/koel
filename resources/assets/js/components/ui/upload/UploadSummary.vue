<template>
  <section class="flex flex-col gap-2" data-testid="upload-summary">
    <p class="self-end text-k-fg-70 tabular-nums">
      {{ formatBytes(sentBytes) }} of {{ formatBytes(totalBytes) }} uploaded
      <span v-if="secondsLeft !== null" :data-seconds="Math.round(secondsLeft)" data-testid="time-left">
        · about {{ secondsToHumanReadable(secondsLeft) }} left
      </span>
    </p>
    <progress
      :max="totalBytes || 1"
      :value="sentBytes"
      class="h-1.5 w-full appearance-none overflow-hidden rounded-full bg-k-fg-10 [&::-moz-progress-bar]:bg-k-highlight [&::-webkit-progress-bar]:bg-k-fg-10 [&::-webkit-progress-value]:bg-k-highlight"
      data-testid="upload-progress"
    />
  </section>
</template>

<script lang="ts" setup>
import { formatBytes, secondsToHumanReadable } from '@/utils/formatters'
import { useUploadProgress } from '@/composables/useUploadProgress'

const { totalBytes, sentBytes, secondsLeft } = useUploadProgress()
</script>
