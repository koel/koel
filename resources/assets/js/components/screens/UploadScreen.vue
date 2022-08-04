<template>
  <section id="uploadWrapper">
    <ScreenHeader layout="collapsed">
      Upload Media

      <template v-slot:controls>
        <BtnGroup uppercased v-if="hasUploadFailures">
          <Btn data-testid="upload-retry-all-btn" green @click="retryAll">
            <icon :icon="faRotateBack"/>
            Retry All
          </Btn>
          <Btn data-testid="upload-remove-all-btn" orange @click="removeFailedEntries">
            <icon :icon="faTimes"/>
            Remove Failed
          </Btn>
        </BtnGroup>
      </template>
    </ScreenHeader>

    <div class="main-scroll-wrap">
      <div
        v-if="mediaPath"
        :class="{ droppable }"
        class="upload-panel"
        @dragenter.prevent="onDragEnter"
        @dragleave.prevent="onDragLeave"
        @drop.stop.prevent="onDrop"
        @dragover.prevent
      >
        <div class="upload-files" v-if="files.length">
          <UploadItem v-for="file in files" :key="file.id" :file="file" data-testid="upload-item"/>
        </div>

        <ScreenEmptyState v-else>
          <template v-slot:icon>
            <icon :icon="faUpload"/>
          </template>

          {{ canDropFolders ? 'Drop files or folders to upload' : 'Drop files to upload' }}

          <span class="secondary d-block">
            <a class="or-click d-block" role="button">
              or click here to select songs
              <input :accept="acceptAttribute" multiple name="file[]" type="file" @change="onFileInputChange"/>
            </a>
          </span>
        </ScreenEmptyState>
      </div>

      <ScreenEmptyState v-else>
        <template v-slot:icon>
          <icon :icon="faWarning"/>
        </template>
        No media path set.
      </ScreenEmptyState>
    </div>
  </section>
</template>

<script lang="ts" setup>
import ismobile from 'ismobilejs'
import { faRotateBack, faTimes, faUpload, faWarning } from '@fortawesome/free-solid-svg-icons'
import { computed, defineAsyncComponent, ref, toRef } from 'vue'

import { settingStore } from '@/stores'
import { eventBus, getAllFileEntries, isDirectoryReadingSupported as canDropFolders } from '@/utils'
import { acceptedMediaTypes, UploadFile } from '@/config'
import { uploadService } from '@/services'
import { useAuthorization } from '@/composables'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'

const BtnGroup = defineAsyncComponent(() => import('@/components/ui/BtnGroup.vue'))
const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))
const UploadItem = defineAsyncComponent(() => import('@/components/ui/upload/UploadItem.vue'))

const acceptAttribute = acceptedMediaTypes.join(',')

const mediaPath = toRef(settingStore.state, 'media_path')
const files = toRef(uploadService.state, 'files')
const droppable = ref(false)
const hasUploadFailures = ref(false)

const { isAdmin } = useAuthorization()
const allowsUpload = computed(() => isAdmin.value && !ismobile.any)

const onDragEnter = () => (droppable.value = allowsUpload.value)
const onDragLeave = () => (droppable.value = false)

const handleFiles = (files: Array<File>) => {
  const uploadCandidates = files
    .filter(file => acceptedMediaTypes.includes(file.type))
    .map((file): UploadFile => ({
      file,
      id: `${file.name}-${file.size}`, // for simplicity, a file's identity is determined by its name and size
      status: 'Ready',
      name: file.name,
      progress: 0
    }))

  uploadService.queue(uploadCandidates)
}

const fileEntryToFile = async (entry: FileSystemEntry) => new Promise<File>(resolve => entry.file(resolve))

const onFileInputChange = (event: InputEvent) => {
  const selectedFileList = (event.target as HTMLInputElement).files
  selectedFileList?.length && handleFiles(Array.from(selectedFileList))
}

const onDrop = async (event: DragEvent) => {
  droppable.value = false

  if (!event.dataTransfer) {
    return
  }

  const fileEntries = await getAllFileEntries(event.dataTransfer.items)
  const files = await Promise.all(fileEntries.map(async entry => await fileEntryToFile(entry)))
  handleFiles(files)
}

const retryAll = () => {
  uploadService.retryAll()
  hasUploadFailures.value = false
}

const removeFailedEntries = () => {
  uploadService.removeFailed()
  hasUploadFailures.value = false
}

eventBus.on('UPLOAD_QUEUE_FINISHED', () => {
  hasUploadFailures.value = uploadService.getFilesByStatus('Errored').length !== 0
})
</script>

<style lang="scss">
#uploadWrapper {
  .upload-panel {
    position: relative;
    height: 100%;
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
