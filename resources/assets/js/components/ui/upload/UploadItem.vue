<template>
  <article class="upload-item relative">
    <div :class="cssClass" class="h-full w-full min-h-[32px] bg-k-fg-5 relative rounded-lg overflow-hidden">
      <div class="absolute z-1 h-full w-full flex items-center">
        <ProgressRing
          v-if="showsProgressRing"
          :title="progressDetails"
          :aria-label="`Upload progress for ${file.name}`"
          :value="file.status === 'Ready' ? 0 : file.progress"
          class="size-4 shrink-0 ml-4 text-k-highlight"
          data-testid="upload-item-progress"
        />
        <Icon
          v-if="isProcessing"
          :icon="faSpinner"
          class="size-4 shrink-0 ml-4 text-k-highlight"
          data-testid="upload-item-processing-spinner"
          spin
        />
        <a
          v-if="file.song"
          :href="url('albums.show', { id: file.song.album_id })"
          class="name min-w-0 flex-1 overflow-hidden whitespace-nowrap px-4 [mask-image:linear-gradient(to_right,black_calc(100%-3rem),transparent)] text-current focus:text-current"
          data-testid="upload-item-album-link"
        >
          {{ file.name }}
        </a>
        <span
          v-else
          class="name min-w-0 flex-1 overflow-hidden whitespace-nowrap px-4 [mask-image:linear-gradient(to_right,black_calc(100%-3rem),transparent)]"
          >{{ file.name }}</span
        >
        <span v-if="showsReasonInRow" class="min-w-0 truncate px-4 text-k-fg-50" data-testid="upload-item-reason">
          {{ file.message }}
        </span>
        <span v-if="file.status === 'Retrying'" class="shrink-0 px-3 text-k-fg-50" data-testid="upload-item-state">
          Retrying&hellip;
        </span>
        <span v-if="canStop || canRetry || canRemove" class="shrink-0 flex gap-1.5 pr-1.5">
          <Btn v-if="canStop" size="small" variant="neutral" @click="stop">Stop</Btn>
          <Btn v-if="canRetry" size="small" variant="success" @click="retry">Retry</Btn>
          <Btn v-if="canRemove" size="small" variant="neutral" @click="remove">Remove</Btn>
        </span>
        <span
          v-if="file.status === 'Uploaded'"
          v-koel-tooltip
          class="px-3 text-k-success"
          data-testid="upload-item-uploaded"
        >
          <Icon :icon="faCircleCheck" />
          <span class="sr-only">Uploaded</span>
        </span>
        <span v-if="isProcessing" class="shrink-0 px-3 text-k-fg-50" data-testid="upload-item-processing">
          Processing
        </span>
      </div>
    </div>
  </article>
</template>

<script lang="ts" setup>
import { faCircleCheck, faExclamationTriangle, faInfoCircle, faSpinner } from '@fortawesome/free-solid-svg-icons'
import { computed, defineAsyncComponent, toRefs } from 'vue'
import { useDialogBox } from '@/composables/useDialogBox'
import type { UploadFile } from '@/services/uploadService'
import { uploadService } from '@/services/uploadService'
import { useRouter } from '@/composables/useRouter'
import { formatBytes } from '@/utils/formatters'

import ProgressRing from '@/components/ui/ProgressRing.vue'

const props = defineProps<{ file: UploadFile }>()

const Btn = defineAsyncComponent(() => import('@/components/ui/form/Btn.vue'))

const { file } = toRefs(props)
const { url } = useRouter()

const isProcessing = computed(() => file.value.status === 'Processing')
const showsProgressRing = computed(() => ['Ready', 'Uploading', 'Retrying'].includes(file.value.status))
const progressDetails = computed(() => {
  const progress = file.value.status === 'Ready' ? 0 : file.value.progress
  const size = file.value.file.size

  return `${Math.round(progress)}% · ${formatBytes(Math.floor((size * progress) / 100))} of ${formatBytes(size)}`
})
const canRetry = computed(() => file.value.status === 'Canceled' || file.value.status === 'Errored')
const canStop = computed(() => file.value.status === 'Uploading')

const canRemove = computed(
  () => !['Uploading', 'Uploaded', 'Skipped'].includes(file.value.status) && !isProcessing.value,
)

const cssClass = computed(() => file.value.status.toLowerCase())
const showsReasonInRow = computed(() => ['Skipped', 'Errored', 'Canceled'].includes(file.value.status))

const { showConfirmDialog } = useDialogBox()

const remove = () => uploadService.remove(file.value)
const retry = () => uploadService.retry(file.value)

const stop = async () => {
  if ((await showConfirmDialog('Stop this upload?')) && file.value.status === 'Uploading') {
    uploadService.abort(file.value)
  }
}
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.uploaded:hover {
  @apply bg-k-fg-10;
}
</style>
