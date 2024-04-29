<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">
        Upload Media

        <template #controls>
          <BtnGroup v-if="hasUploadFailures" uppercased>
            <Btn data-testid="upload-retry-all-btn" success @click="retryAll">
              <Icon :icon="faRotateRight" />
              Retry All
            </Btn>
            <Btn data-testid="upload-remove-all-btn" highlight @click="removeFailedEntries">
              <Icon :icon="faTrashCan" />
              Remove Failed
            </Btn>
          </BtnGroup>
        </template>
      </ScreenHeader>
    </template>

    <div
      v-if="mediaPathSetUp"
      :class="{ droppable }"
      class="relative flex-1 flex flex-col"
      @dragenter.prevent="onDragEnter"
      @dragleave.prevent="onDragLeave"
      @drop.prevent="onDrop"
      @dragover.prevent
    >
      <div v-if="files.length" class="pb-4 space-y-3">
        <UploadItem v-for="file in files" :key="file.id" :file="file" data-testid="upload-item" />
      </div>

      <ScreenEmptyState v-else>
        <template #icon>
          <Icon :icon="faUpload" />
        </template>

        {{ canDropFolders ? 'Drop files or folders to upload' : 'Drop files to upload' }}

        <span class="secondary block">
          <a class="block relative" role="button">
            or click here to select songs
            <input
              :accept="acceptAttribute"
              class="absolute opacity-0 w-full h-full z-[2] cursor-pointer left-0 top-0"
              multiple
              name="file[]"
              type="file"
              @change="onFileInputChange"
            >
          </a>
        </span>
      </ScreenEmptyState>
    </div>

    <ScreenEmptyState v-else>
      <template #icon>
        <Icon :icon="faWarning" />
      </template>
      No media path set.
    </ScreenEmptyState>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faRotateRight, faTrashCan, faUpload, faWarning } from '@fortawesome/free-solid-svg-icons'
import { computed, defineAsyncComponent, ref, toRef } from 'vue'

import { isDirectoryReadingSupported as canDropFolders } from '@/utils'
import { acceptedMediaTypes } from '@/config'
import { uploadService } from '@/services'
import { useUpload } from '@/composables'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import BtnGroup from '@/components/ui/form/BtnGroup.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'

const Btn = defineAsyncComponent(() => import('@/components/ui/form/Btn.vue'))
const UploadItem = defineAsyncComponent(() => import('@/components/ui/upload/UploadItem.vue'))

const acceptAttribute = acceptedMediaTypes.join(',')

const { allowsUpload, mediaPathSetUp, queueFilesForUpload, handleDropEvent } = useUpload()

const files = toRef(uploadService.state, 'files')
const droppable = ref(false)

const hasUploadFailures = computed(() => files.value.filter(({ status }) => status === 'Errored').length > 0)

const onDragEnter = () => (droppable.value = allowsUpload.value)

const onDragLeave = (e: MouseEvent) => {
  if ((e.currentTarget as Node)?.contains?.(e.relatedTarget as Node)) {
    return
  }

  droppable.value = false
}

const onFileInputChange = (event: Event) => {
  const selectedFileList = (event.target as HTMLInputElement).files

  if (selectedFileList?.length) {
    queueFilesForUpload(Array.from(selectedFileList))
  }
}

const onDrop = async (event: DragEvent) => {
  droppable.value = false
  await handleDropEvent(event)
}

const retryAll = () => uploadService.retryAll()
const removeFailedEntries = () => uploadService.removeFailed()
</script>

<style lang="postcss" scoped>
.droppable {
  @apply border-2 border-dashed border-white/40 bg-black/20 rounded-3xl;
}
</style>
