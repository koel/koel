<template>
  <section id="uploadWrapper">
    <ScreenHeader>
      Upload Media <sup>Beta</sup>

      <template v-slot:controls>
        <BtnGroup uppercased v-if="hasUploadFailures">
          <Btn data-testid="upload-retry-all-btn" green @click="retryAll">
            <i class="fa fa-repeat"></i>
            Retry All
          </Btn>
          <Btn data-testid="upload-remove-all-btn" orange @click="removeFailedEntries">
            <i class="fa fa-times"></i>
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
          <UploadItem v-for="file in files" :key="file.id" :file="file" data-test="upload-item"/>
        </div>

        <ScreenEmptyState v-else>
          <template v-slot:icon>
            <i class="fa fa-upload"></i>
          </template>

          {{ instructionText }}
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
          <i class="fa fa-exclamation-triangle"></i>
        </template>
        No media path set.
      </ScreenEmptyState>
    </div>
  </section>
</template>

<script lang="ts" setup>
import ismobile from 'ismobilejs'
import md5 from 'blueimp-md5'
import { computed, defineAsyncComponent, ref, toRef } from 'vue'

import { settingStore, userStore } from '@/stores'
import { eventBus, getAllFileEntries, isDirectoryReadingSupported } from '@/utils'
import { acceptedMediaTypes, UploadFile } from '@/config'
import { uploadService } from '@/services'

import UploadItem from '@/components/ui/upload/UploadItem.vue'
import BtnGroup from '@/components/ui/BtnGroup.vue'
import Btn from '@/components/ui/Btn.vue'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/ScreenHeader.vue'))
const ScreenEmptyState = defineAsyncComponent(() => import('@/components/ui/ScreenEmptyState.vue'))

const acceptAttribute = acceptedMediaTypes.join(',')

const mediaPath = toRef(settingStore.state, 'media_path')
const files = toRef(uploadService.state, 'files')
const droppable = ref(false)
const hasUploadFailures = ref(false)

const allowsUpload = computed(() => userStore.state.current.is_admin && !ismobile.any)

const instructionText = isDirectoryReadingSupported
  ? 'Drop files or folders to upload'
  : 'Drop files to upload'

const onDragEnter = () => (droppable.value = allowsUpload.value)
const onDragLeave = () => (droppable.value = false)

const handleFiles = (files: Array<File>) => {
  const uploadCandidates = files
    .filter(file => acceptedMediaTypes.includes(file.type))
    .map((file): UploadFile => ({
      file,
      id: md5(`${file.name}-${file.size}`), // for simplicity, a file's identity is determined by its name and size
      status: 'Ready',
      name: file.name,
      progress: 0
    }))

  uploadService.queue(uploadCandidates)
}

const fileEntryToFile = async (entry: FileSystemEntry): Promise<File> => new Promise(resolve => entry.file(resolve))

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
  sup {
    vertical-align: super;
    font-size: .4em;
    text-transform: uppercase;
    opacity: .5;
  }

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
