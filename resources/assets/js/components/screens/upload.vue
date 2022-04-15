<template>
  <section id="uploadWrapper">
    <ScreenHeader>
      Upload Media <sup>Beta</sup>

      <template v-slot:controls>
        <BtnGroup uppercased v-if="hasUploadFailures">
          <Btn @click="retryAll" green data-testid="upload-retry-all-btn">
            <i class="fa fa-repeat"></i>
            Retry All
          </Btn>
          <Btn @click="removeFailedEntries" orange data-testid="upload-remove-all-btn">
            <i class="fa fa-times"></i>
            Remove Failed
          </Btn>
        </BtnGroup>
      </template>
    </ScreenHeader>

    <div class="main-scroll-wrap">
      <div
        class="upload-panel"
        @dragenter.prevent="onDragEnter"
        @dragleave.prevent="onDragLeave"
        @drop.stop.prevent="onDrop"
        @dragover.prevent
        :class="{ droppable }"
        v-if="mediaPath"
      >
        <div class="upload-files" v-if="uploadState.files.length">
          <UploadItem v-for="file in uploadState.files" :key="file.id" :file="file" data-test="upload-item"/>
        </div>

        <ScreenPlaceholder v-else>
          <template v-slot:icon>
            <i class="fa fa-upload"></i>
          </template>

          {{ instructionText }}
          <span class="secondary d-block">
            <a class="or-click d-block" role="button">
              or click here to select songs
              <input type="file" name="file[]" multiple @change="onFileInputChange"/>
            </a>
          </span>
        </ScreenPlaceholder>
      </div>

      <ScreenPlaceholder v-else>
        <template v-slot:icon>
          <i class="fa fa-exclamation-triangle"></i>
        </template>
        No media path set.
      </ScreenPlaceholder>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, reactive, ref, toRef } from 'vue'
import ismobile from 'ismobilejs'
import md5 from 'blueimp-md5'

import { settingStore, userStore } from '@/stores'
import { eventBus, getAllFileEntries, isDirectoryReadingSupported } from '@/utils'
import { UploadFile, validMediaMimeTypes } from '@/config'
import { upload } from '@/services'

import UploadItem from '@/components/ui/upload/upload-item.vue'
import BtnGroup from '@/components/ui/btn-group.vue'
import Btn from '@/components/ui/btn.vue'

const ScreenHeader = defineAsyncComponent(() => import('@/components/ui/screen-header.vue'))
const ScreenPlaceholder = defineAsyncComponent(() => import('@/components/ui/screen-placeholder.vue'))

const mediaPath = toRef(settingStore.state, 'media_path')
const droppable = ref(false)
const userState = reactive(userStore.state)
const uploadState = reactive(upload.state)
const hasUploadFailures = ref(false)

const allowsUpload = computed(() => userState.current.is_admin && !ismobile.any)

const instructionText = computed(() => isDirectoryReadingSupported
  ? 'Drop files or folders to upload'
  : 'Drop files to upload'
)

const onDragEnter = () => (droppable.value = allowsUpload.value)
const onDragLeave = () => (droppable.value = false)

const handleFiles = (files: Array<File>) => {
  const uploadCandidates = files
    .filter(file => validMediaMimeTypes.includes(file.type))
    .map((file): UploadFile => ({
      file,
      id: md5(`${file.name}-${file.size}`), // for simplicity, a file's identity is determined by its name and size
      status: 'Ready',
      name: file.name,
      progress: 0
    }))

  upload.queue(uploadCandidates)
}

const fileEntryToFile = async (entry: FileSystemEntry): Promise<File> => new Promise(resolve => {
  entry.file(resolve)
})

const onFileInputChange = (event: InputEvent) => {
  const selectedFileList = (event.target as HTMLInputElement).files
  selectedFileList && handleFiles(Array.from(selectedFileList))
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
  upload.retryAll()
  hasUploadFailures.value = false
}

const removeFailedEntries = () => {
  upload.removeFailed()
  hasUploadFailures.value = false
}

eventBus.on('UPLOAD_QUEUE_FINISHED', () => (hasUploadFailures.value = upload.getFilesByStatus('Errored').length !== 0))
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
