<template>
  <div class="upload-item" :class="[file.status]" :title="file.message">
    <span class="progress" :style="{ width: `${file.progress}%` }"></span>
    <span class="details">
      <span class="name">{{ file.name }}</span>
      <span class="controls">
        <Btn
          @click="retry"
          title="Retry upload"
          transparent
          unrounded
          icon-only
          v-if="canRetry"
          data-test="retry-upload-btn"
        >
          <i class="fa fa-repeat"></i>
        </Btn>
        <Btn
          @click="remove"
          title="Remove"
          transparent
          unrounded
          icon-only
          v-if="canRemove"
          data-test="remove-upload-btn"
        >
          <i class="fa fa-times"></i>
        </Btn>
      </span>
    </span>
  </div>
</template>

<script lang="ts">
import Vue, { PropOptions } from 'vue'
import { UploadFile } from '@/config'
import { upload } from '@/services'

export default Vue.extend({
  props: {
    file: {
      type: Object,
      required: true
    } as PropOptions<UploadFile>
  },

  components: {
    Btn: () => import('@/components/ui/btn.vue')
  },

  computed: {
    canRetry (): boolean {
      return this.file.status === 'Canceled' || this.file.status === 'Errored'
    },

    canRemove (): boolean {
      // we're not supporting cancel tokens (yet).
      return this.file.status !== 'Uploading'
    }
  },

  methods: {
    remove (): void {
      upload.remove(this.file)
    },

    retry (): void {
      upload.retry(this.file)
    }
  }
})
</script>

<style lang="scss" scoped>
.upload-item {
  position: relative;
  margin-bottom: 5px;
  border-radius: 3px;
  min-height: 32px;
  overflow: hidden;
  background: var(--color-bg-secondary);

  .progress {
    position: absolute;
    height: 100%;
    top: 0;
    left: 0;
    background: var(--color-highlight);
    z-index: 0;
    transition: .3s ease-out;
  }

  &.Uploaded {
    background: var(--color-green);

    .progress {
      background: transparent;
    }
  }

  &.Errored {
    background: var(--color-red);

    .progress {
      background: transparent;
    }
  }

  .details {
    z-index: 1;
    position: absolute;
    height: 100%;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;

    .name {
      padding: 0 12px;
    }
  }

  .controls {
    display: flex;
  }

  button {
    padding: 8px 8px;
    background: rgba(0, 0, 0, .1);
  }
}
</style>
