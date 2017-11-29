<template>
  <li class="upload-item" :class="'upload-item-' + file.status">
    <p>
      <span>{{ file.name }}</span>
      <span class="progress">
        <span class="done" :style="{ width: currentPercentage + '%' }"></span>
      </span>
    </p>
    <!-- <button>Remove</button> -->
  </li>
</template>

<script>
import axios from 'axios'

export default {
  props: {
    file: {
      type: File,
      required: true
    }
  },

  data () {
    return {
      currentPercentage: 0
    }
  },

  methods: {
    upload () {
      const data = new FormData()
      const config = {
        onUploadProgress: event => {
          this.currentPercentage = Math.round(event.loaded * 100 / event.total)
        }
      }
      this.file.status = 'uploading'
      data.append('file', this.file)
      axios.post('songs', data, config).then(res => {
        this.file.status = 'success'
        this.$emit('done', this.file)
      }).catch(error => {
        this.file.status = 'error'
        this.$emit('errored', this.file)
      })
    },

    remove () {
      this.$emit('remove', this.file)
    }
  }
}
</script>

<style lang="scss" scoped>
@import "../../../sass/partials/_vars.scss";
@import "../../../sass/partials/_mixins.scss";

.upload-item {
  margin-bottom: 15px;

  p {
    display: inline-block;
  }
}

.progress {
  display: block;
  margin-top: 5px;
  height: 2px;
  position: relative;
  background: $colorGrey;

  .done {
    display: block;
    height: 100%;
    transition: width .3s;
    background: $colorOrange;
  }
}
</style>
