<template>
  <article class="flex flex-col items-center min-w-[24px]">
    <span ref="sliderEl" class="slider h-[100px]" />
    <label class="mt-2 mb-0 text-left text-sm text-k-text-primary">
      <slot />
    </label>
  </article>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import noUiSlider from 'nouislider'

const props = withDefaults(defineProps<{
  type?: 'preamp' | 'gain',
  modelValue?: number
}>(), {
  type: 'gain',
  modelValue: 0
})

const sliderEl = ref<EqualizerBandElement>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: number): void
  (e: 'commit')
}>()

const value = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value)
})

/**
 * Since watching the value and update the slider UI proves to be not performant,
 * we defined an explicit method to update the UI and expose it so that the
 * parent component (Equalizer) can call it when resetting the preset.
 */
const updateSliderValue = (val: number) => {
  sliderEl.value?.noUiSlider.set(val)
  value.value = val
}

onMounted(() => {
  noUiSlider.create(sliderEl.value!, {
    connect: [false, true],
    // the first element is the preamp. The rest are gains.
    start: value.value,
    range: { min: -20, max: 20 },
    orientation: 'vertical',
    direction: 'rtl'
  })

  sliderEl.value!.noUiSlider.on('slide', (values, handle) => {
    emit('update:modelValue', parseFloat(values[handle]))
  })

  sliderEl.value!.noUiSlider.on('change', () => emit('commit'))
})

defineExpose({
  updateSliderValue
})
</script>

<style lang="postcss">
/* overriding the global noUi import, don't scope */
/* also, don't use Tailwind here as it will mess things up */
/* and wrap the styles in a class to ensure cascading for built assets */
article {
  .noUi {
    &-connect {
      background: none;
      box-shadow: none;

      &::after {
        content: " ";
        position: absolute;
        width: 2px;
        height: 100%;
        top: 0;
        left: 7px;
      }
    }

    &-touch-area {
      cursor: ns-resize;
    }

    &-target {
      background: transparent;
      border-radius: 0;
      border: 0;
      box-shadow: none;
      width: 16px;

      &::after {
        content: " ";
        position: absolute;
        width: 2px;
        height: 100%;
        background: linear-gradient(to bottom, var(--color-highlight) 0%, var(--color-highlight) 36%, var(--color-success) 100%);
        background-size: 2px;
        top: 0;
        left: 7px;
      }
    }

    &-handle {
      border: 0;
      border-radius: 0;
      box-shadow: none;
      cursor: pointer;

      &::before, &::after {
        display: none;
      }
    }

    &-vertical {
      .noUi-handle {
        width: 16px;
        height: 6px;
        left: -16px;
        border-radius: 9999px;
        top: 0;
      }
    }
  }

  .noUi-handle {
    border: 0;
    border-radius: 0;
    box-shadow: none;
    cursor: pointer;

    &::before, &::after {
      display: none;
    }
  }
}
</style>
