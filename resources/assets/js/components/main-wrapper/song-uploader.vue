<template>
  <div id="songUploader" ref="dropZone" v-show="showing" @click="showing = false">
    <p v-if="files.length">{{ files.length | pluralize('file') }} in queue</p>
    <div class="current" v-if="currentFile">
      <p>Now uploading: <span class="name">{{ currentFile.name }}</span></p>
      <div class="progress">
        <div class="done" :style="{ width: currentPercentage + '%' }"></div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { take, takeRight } from 'lodash'
import { pluralize } from '@/utils'

const allowedExtensions = ['mp3', 'aac', 'm4a', 'ogg']
const chunkSize = 5

export default {
  filters: { pluralize },

  data () {
    return {
      showing: false,
      files: [],
      remainingFiles: [],
      currentFile: null,
      currentPercentage: 0,
      uploading: false
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
      })

      if (!applicableFiles.length) {
        this.showing = false
      }

      this.queue(applicableFiles)
    },

    queue (files) {
      this.files = this.files.concat(files)
      this.uploading || this.startUpload()
    },

    startUpload () {
      this.uploading = true
      this.upload(this.currentFile = this.files.shift())
    },

    upload (file) {
      const data = new FormData()
      const config = {
        onUploadProgress: event => {
          this.currentPercentage = Math.round(event.loaded * 100 / event.total)
        }
      }
      data.append('file', file)
      axios.post('songs', data, config).then(res => {
        if (this.files.length) {
          this.upload(this.currentFile = this.files.shift())
        } else {
          this.uploading = false
          this.currentFile = null
          alert('All done')
        }
      })
    }
  },

  mounted () {
    window.addEventListener('dragenter', e => {
      this.showing = e.dataTransfer.types && e.dataTransfer.types.indexOf('Files') !== -1
    })

    this.$refs.dropZone.addEventListener('dragenter', this.allowDrop)
    this.$refs.dropZone.addEventListener('dragover', this.allowDrop)
    this.$refs.dropZone.addEventListener('dragleave', () => {
      this.showing = false
    })

    this.$refs.dropZone.addEventListener('drop', this.handleDrop);
  }
}
</script>

<style lang="scss">
@import "../../../sass/partials/_vars.scss";
@import "../../../sass/partials/_mixins.scss";
@import "../../../sass/partials/_shared.scss";

#songUploader {
  position: fixed;
  width: 100%;
  height: 100%;
  z-index: 9999;
  background: rgba(0, 0, 0, .8);
  top: 0;
  left: 0;
  text-align: center;

  .progress {
    height: 8px;
    border: 1px solid $colorGrey;
    border-radius: 4px;
    width: 320px;
    margin: 0 auto;
    position: relative;

    .done {
      height: 100%;
      background: $colorOrange;
    }
  }
}
</style>
