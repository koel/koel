<template>
  <article :title="file.message" class="upload-item relative">
    <div :class="cssClass" class="h-full w-full min-h-[32px] bg-k-fg-5 relative rounded-lg overflow-hidden">
      <div class="absolute z-1 h-full w-full flex items-center">
        <span class="name px-4 flex-1 flex items-center">{{ file.name }}</span>
        <Btn v-if="canRetry" class="!px-3" icon-only title="Retry" transparent unrounded @click="retry">
          <Icon :icon="faRotateBack" />
        </Btn>
        <Btn v-if="canAbort" class="!px-3" icon-only title="Abort" transparent unrounded @click="abort">
          <Icon :icon="faXmark" />
        </Btn>
        <Btn v-if="canRemove" class="!px-3" icon-only title="Remove" transparent unrounded @click="remove">
          <Icon :icon="faTrashCan" />
        </Btn>
      </div>
    </div>
    <p class="text-[.90rem] mt-1 ml-4">
      <span v-if="file.status === 'Errored'" class="text-k-danger">
        <Icon :icon="faExclamationCircle" class="mr-1" />
        {{ file.message }}
      </span>
      <span v-if="file.status === 'Canceled'">Canceled.</span>
      <span v-if="file.status === 'Ready'">Queued.</span>
      <span v-if="file.status === 'Uploading'">
        Uploading
        <span class="tabular-nums">
          <strong>{{ Math.round(file.progress * 100) / 100 }}</strong
          >%
        </span>
      </span>
      <span v-if="file.status === 'Uploaded'" class="text-k-success">
        <Icon :icon="faCheckCircle" class="mr-1" />
        Uploaded.
      </span>
    </p>
  </article>
</template>

<script lang="ts" setup>
import {
  faCheckCircle,
  faExclamationCircle,
  faExclamationTriangle,
  faInfoCircle,
  faRotateBack,
  faTrashCan,
  faXmark,
} from '@fortawesome/free-solid-svg-icons'
import { computed, defineAsyncComponent, toRefs } from 'vue'
import { useDialogBox } from '@/composables/useDialogBox'
import type { UploadFile } from '@/services/uploadService'
import { uploadService } from '@/services/uploadService'

const props = defineProps<{ file: UploadFile }>()

const Btn = defineAsyncComponent(() => import('@/components/ui/form/Btn.vue'))

const { file } = toRefs(props)

const canRetry = computed(() => file.value.status === 'Canceled' || file.value.status === 'Errored')
const canAbort = computed(() => file.value.status === 'Uploading')
const canRemove = computed(() => file.value.status !== 'Uploading')
const cssClass = computed(() => file.value.status.toLowerCase())
const progressBarWidth = computed(() => (file.value.status === 'Uploading' ? `${file.value.progress}%` : '0'))

const { showConfirmDialog } = useDialogBox()

const remove = () => uploadService.remove(file.value)
const retry = () => uploadService.retry(file.value)

const abort = async () => {
  if ((await showConfirmDialog('Abort this upload?')) && file.value.status === 'Uploading') {
    uploadService.abort(file.value)
  }
}
</script>

<style lang="postcss" scoped>
article > div::before {
  width: v-bind(progressBarWidth);
  content: '';
  @apply absolute h-full top-0 left-0 z-0 duration-200 ease-out bg-k-highlight;
}

.uploaded {
  @apply bg-k-success;
}

.errored {
  @apply bg-k-danger;
}
</style>
