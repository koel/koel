<template>
  <div :class="cssClass" :title="file.message" class="upload-item">
    <span :style="{ width: `${file.progress}%` }" class="progress"/>
    <span class="details">
      <span class="name">{{ file.name }}</span>
      <span class="controls">
        <Btn v-if="canRetry" icon-only title="Retry" transparent unrounded @click="retry">
          <icon :icon="faRotateBack"/>
        </Btn>
        <Btn v-if="canRemove" icon-only title="Remove" transparent unrounded @click="remove">
          <icon :icon="faTimes"/>
        </Btn>
      </span>
    </span>
  </div>
</template>

<script lang="ts" setup>
import slugify from 'slugify'
import { faRotateBack, faTimes } from '@fortawesome/free-solid-svg-icons'
import { computed, defineAsyncComponent, toRefs } from 'vue'
import { UploadFile, uploadService } from '@/services'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))

const props = defineProps<{ file: UploadFile }>()
const { file } = toRefs(props)

const canRetry = computed(() => file.value.status === 'Canceled' || file.value.status === 'Errored')
const canRemove = computed(() => file.value.status !== 'Uploading') // we're not supporting cancel tokens (yet).
const cssClass = computed(() => slugify(file.value.status).toLowerCase())

const remove = () => uploadService.remove(file.value)
const retry = () => uploadService.retry(file.value)
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

  &.uploaded {
    background: var(--color-green);

    .progress {
      background: transparent;
    }
  }

  &.errored {
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
