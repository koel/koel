<template>
  <section id="uploadWrapper" ref="dropZone">
    <h1 class="heading">Upload</h1>

    <div class="main-scroll-wrap">
      <section class="details" v-if="files.length">
        <transition-group name="file-list" tag="ul">
          <li
            v-for="file in files"
            class="file-list-item"
            is="upload-item"
            ref="uploadItems"
            :file="file"
            :key="file.id"
            @done="fileUploadDone"
            @errored="fileUploadErrored"
            @remove="removeFile"
            ></li>
        </transition-group>
      </section>
      <p v-else>Drag and drop audio files here to start uploading.</p>
    </div>
  </section>
</template>
<script>
import { without, uniqueId, filter, take } from 'lodash'
import { alerts, event } from '@/utils'
import UploadItem from '@/components/shared/upload-item.vue'

const allowedExtensions = ['mp3', 'aac', 'm4a', 'ogg', 'flac']
const maxConcurrentUploads = 5

export default {
  components: { UploadItem },

  data () {
    return {
      files: [],
      uploading: false
    }
  },

  watch: {
    uploading (value) {
      event.emit('upload:status', value)
    }
  },

  methods: {
    allowDrop (e) {
      e.dataTransfer.dropEffect = 'copy'
      e.preventDefault()
    },

    handleDrop (e) {
      e.preventDefault()
      const applicableFiles = Array.from(e.dataTransfer.files).filter(file => {
        const ext = /(?:\.([^.]+))?$/.exec(file.name)[1]
        return allowedExtensions.indexOf(ext) !== -1
          && (file.id = uniqueId())
          && (file.status = 'ready')
      })

      this.files = this.files.concat(applicableFiles)
      this.uploading || this.$nextTick(this.uploadNextBatch)
    },

    uploadNextBatch () {
      this.uploading = true
      const uploadingFileCount = filter(this.files, file => file.status === 'uploading').length
      const nextBatchLength = maxConcurrentUploads - uploadingFileCount

      const nextBatch = take(filter(this.files, file => file.status === 'ready'), nextBatchLength)
      nextBatch.forEach(file => {
        const fileIndex = this.files.indexOf(file)
        this.$refs.uploadItems[fileIndex].upload()
      })
    },

    fileUploadDone (file) {
      this.files = without(this.files, file)
      if (this.isCompleted()) {
        return this.complete()
      } else {
        alerts.success(`Uploaded "${file.name}".`)
      }

      this.$nextTick(this.uploadNextBatch)
    },

    fileUploadErrored (file) {
      if (this.isCompleted()) {
        return this.complete()
      } else {
        alerts.error(`Failed to upload "${file.name}".`)
      }

      this.$nextTick(this.uploadNextBatch)
    },

    removeFile (file) {

    },

    isCompleted () {
      return filter(this.files, file => {
        return file.status === 'ready' || file.status === 'uploading'
      }).length === 0
    },

    complete () {
      this.uploading = false
      alerts.success('Upload completed.')
    }
  },

  mounted () {
    this.$refs.dropZone.addEventListener('dragenter', this.allowDrop)
    this.$refs.dropZone.addEventListener('dragover', this.allowDrop)
    this.$refs.dropZone.addEventListener('dragleave', () => {
      this.showing = false
    })

    this.$refs.dropZone.addEventListener('drop', this.handleDrop);
  }
}
</script>

<style lang="scss" scoped>
.file-list-item {
  transition: all 1s;
}

.file-list-leave-to {
  opacity: 0;
}
</style>
