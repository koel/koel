<template>
  <section class="flex flex-col gap-2" data-testid="upload-summary">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
      <p
        class="flex flex-wrap gap-2 [&>span+span]:before:mr-2 [&>span+span]:before:text-k-fg-50 [&>span+span]:before:content-['·']"
      >
        <span v-if="counts.uploaded" :data-count="counts.uploaded" data-testid="uploaded-count">
          <strong class="font-semibold tabular-nums text-k-success">{{ counts.uploaded }}</strong> uploaded
        </span>
        <span v-if="counts.inProgress" :data-count="counts.inProgress" data-testid="in-progress-count">
          <strong class="font-semibold tabular-nums text-k-highlight">{{ counts.inProgress }}</strong> in progress
        </span>
        <span v-if="counts.failed" :data-count="counts.failed" data-testid="failed-count">
          <strong class="font-semibold tabular-nums text-k-danger">{{ counts.failed }}</strong> failed
        </span>
        <span v-if="counts.skipped" :data-count="counts.skipped" data-testid="skipped-count">
          <strong class="font-semibold tabular-nums text-k-fg-50">{{ counts.skipped }}</strong> skipped
        </span>
      </p>
      <p class="text-k-fg-70 tabular-nums">
        {{ formatBytes(sentBytes) }} of {{ formatBytes(totalBytes) }}
        <span v-if="secondsLeft !== null" :data-seconds="Math.round(secondsLeft)" data-testid="time-left">
          · about {{ secondsToHumanReadable(secondsLeft) }} left
        </span>
      </p>
    </div>
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

const { counts, totalBytes, sentBytes, secondsLeft } = useUploadProgress()
</script>
