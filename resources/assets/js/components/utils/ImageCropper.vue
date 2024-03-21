<template>
  <div class="cropper-wrapper">
    <div>
      <Cropper
        ref="cropper"
        :src="source"
        :stencil-props="{ aspectRatio: 1 }"
        :min-width="config.minWidth"
        :max-width="config.maxWidth"
      />
      <div class="controls">
        <Btn type="button" green @click.prevent="crop">Crop</Btn>
        <Btn type="button" red @click.prevent="cancel">Cancel</Btn>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, toRefs } from 'vue'
import { Cropper } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'

import Btn from '@/components/ui/Btn.vue'

const props = withDefaults(defineProps<{
  source?: string | null
  config?: {
    minWidth: number
    maxWidth: number
  }
}>(), {
  source: null,
  config: () => ({
    minWidth: 192,
    maxWidth: 480
  })
})

const { source, config } = toRefs(props)
const cropper = ref<typeof Cropper>()

const emits = defineEmits<{
  (e: 'crop', result: string): void
  (e: 'cancel'): void
}>()

const crop = () => emits('crop', cropper.value?.getResult().canvas.toDataURL())
const cancel = () => emits('cancel')
</script>


<style scoped lang="scss">
.cropper-wrapper {
  width: 100%;
  height: 100%;
  position: fixed;
  top: 0;
  left: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 99;
  background: rgba(0, 0, 0, .5);

  > div {
    position: relative;
    max-width: 100%;
    max-height: 100%;
    border-radius: 5px;
    display: flex;
  }

  .controls {
    position: fixed;
    right: 1.5rem;
    top: 1.5rem;
    display: flex;
    gap: .5rem;
    flex: 1;
  }
}
</style>
