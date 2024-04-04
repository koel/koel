<template>
  <div
    :class="cssClass"
    :title="file.message"
    class="upload-item relative rounded min-h-[32px] overflow-hidden bg-k-bg-secondary"
  >
    <span
      :style="{ width: `${file.progress}%` }"
      class="absolute h-full top-0 left-0 z-0 duration-200 ease-out bg-k-highlight"
    />
    <span class="details z-10 absolute h-full w-full flex items-center content-between">
      <span class="name px-4 flex-1">{{ file.name }}</span>
      <span class="flex items-center">
        <span v-if="file.status === 'Errored'" v-koel-tooltip.left :title="file.message" class="info !px-3">
          <Icon :icon="faInfoCircle" :title="file.message" />
        </span>
        <Btn v-if="canRetry" icon-only title="Retry" transparent unrounded class="!px-3" @click="retry">
          <Icon :icon="faRotateBack" />
        </Btn>
        <Btn v-if="canRemove" icon-only title="Remove" transparent unrounded class="!px-3" @click="remove">
          <Icon :icon="faTrashCan" />
        </Btn>
      </span>
    </span>
  </div>
</template>

<script lang="ts" setup>
import slugify from 'slugify'
import { faInfoCircle, faRotateBack, faTrashCan } from '@fortawesome/free-solid-svg-icons'
import { computed, defineAsyncComponent, toRefs } from 'vue'
import { UploadFile, uploadService } from '@/services'

const Btn = defineAsyncComponent(() => import('@/components/ui/form/Btn.vue'))

const props = defineProps<{ file: UploadFile }>()
const { file } = toRefs(props)

const canRetry = computed(() => file.value.status === 'Canceled' || file.value.status === 'Errored')
const canRemove = computed(() => file.value.status !== 'Uploading') // we're not supporting cancel tokens (yet).
const cssClass = computed(() => slugify(file.value.status).toLowerCase())

const remove = () => uploadService.remove(file.value)
const retry = () => uploadService.retry(file.value)
</script>

<style lang="postcss" scoped>
.uploaded {
  @apply bg-k-success;

  .progress {
    @apply bg-transparent;
  }
}

.errored {
  @apply bg-k-danger;

  .progress {
    @apply bg-transparent;
  }
}
</style>
