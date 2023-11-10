<template>
  <section id="uploadWrapper">
    <ScreenHeader layout="collapsed">
      Upload Media

      <template #controls>
        <BtnGroup v-if="hasUploadFailures" uppercased>
          <Btn data-testid="upload-retry-all-btn" green @click="retryAll">
            <Icon :icon="faRotateRight" />
            Retry All
          </Btn>
          <Btn data-testid="upload-remove-all-btn" orange @click="removeFailedEntries">
            <Icon :icon="faTrashCan" />
            Remove Failed
          </Btn>
        </BtnGroup>
      </template>
    </ScreenHeader>

    <div v-koel-overflow-fade class="main-scroll-wrap">
      <div
        v-if="mediaPathSetUp"
        :class="{ droppable }"
        class="upload-panel"
        @dragenter.prevent="onDragEnter"
        @dragleave.prevent="onDragLeave"
        @drop.prevent="onDrop"
        @dragover.prevent
      >
        <div v-if="files.length" class="upload-files">
          <UploadItem v-for="file in files" :key="file.id" :file="file" data-testid="upload-item" />
        </div>

        <ScreenEmptyState v-else>
          <template #icon>
            <Icon :icon="faUpload" />
          </template>

          {{ canDropFolders ? 'Drop files or folders to upload' : 'Drop files to upload' }}

          <span class="secondary d-block">
            <a class="or-click d-block" role="button">
              or click here to select songs
              <input :accept="acceptAttribute" multiple name="file[]" type="file" @change="onFileInputChange">
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
    </div>
  </section>
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

const BtnGroup = defineAsyncComponent(() => import('@/components/ui/BtnGroup.vue'))
const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))
const UploadItem = defineAsyncComponent(() => import('@/components/ui/upload/UploadItem.vue'))

const acceptAttribute = acceptedMediaTypes.join(',')

const { allowsUpload, mediaPathSetUp, queueFilesForUpload, handleDropEvent } = useUpload()

const files = toRef(uploadService.state, 'files')
const droppable = ref(false)

const hasUploadFailures = computed(() => files.value.filter((file) => file.status === 'Errored').length > 0)

const onDragEnter = () => (droppable.value = allowsUpload.value)
const onDragLeave = () => (droppable.value = false)

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

<style lang="scss">
#uploadWrapper {
  .upload-panel {
    position: relative;
    flex: 1;
    display: flex;
    flex-direction: column;
  }

  .upload-files {
    padding-bottom: 1rem;
  }

  input[type=file] {
    position: absolute;
    top: 0;
    left: 0;
    opacity: 0;
    width: 100%;
    height: 100%;
    z-index: 2;
    cursor: pointer;
  }

  a.or-click {
    position: relative;
  }
}
</style>
