<template>
  <div :class="[file.status]" :title="file.message" class="upload-item">
    <span :style="{ width: `${file.progress}%` }" class="progress"></span>
    <span class="details">
      <span class="name">{{ file.name }}</span>
      <span class="controls">
        <Btn
          v-if="canRetry"
          data-test="retry-upload-btn"
          icon-only
          title="Retry upload"
          transparent
          unrounded
          @click="retry"
        >
          <i class="fa fa-repeat"></i>
        </Btn>
        <Btn
          v-if="canRemove"
          data-test="remove-upload-btn"
          icon-only
          title="Remove"
          transparent
          unrounded
          @click="remove"
        >
          <i class="fa fa-times"></i>
        </Btn>
      </span>
    </span>
  </div>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, toRefs } from 'vue'
import { UploadFile } from '@/config'
import { upload } from '@/services'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))

const props = defineProps<{ file: UploadFile }>()
const { file } = toRefs(props)

const canRetry = computed(() => file.value.status === 'Canceled' || file.value.status === 'Errored')
const canRemove = computed(() => file.value.status !== 'Uploading') // we're not supporting cancel tokens (yet).

const remove = () => upload.remove(file.value)
const retry = () => upload.retry(file.value)
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
