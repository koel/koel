<template>
  <form
    :class="previewing && 'previewing'"
    class="flex flex-col gap-4"
    data-testid="create-theme-form"
    @submit.prevent="handleSubmit"
    @keydown.esc="maybeClose"
  >
    <header>
      <h1>New Theme</h1>
    </header>

    <main>
      <div class="grid grid-cols-[max-content_1fr] gap-x-5 gap-y-5">
        <label for="themeName">Name</label>
        <TextInput id="themeName" v-model="data.name" v-koel-focus placeholder="My Fancy Theme" required title="Name" />

        <label>Colors</label>
        <div>
          <div class="inline-grid grid-cols-3 gap-4 items-center">
            <ColorPicker v-model="data.fg_color" title="Foreground color">
              <Icon :icon="faFont" size="lg" />
            </ColorPicker>

            <ColorPicker v-model="data.bg_color" title="Background color">
              <Icon :icon="faFillDrip" size="lg" />
            </ColorPicker>

            <ColorPicker v-model="data.highlight_color" title="Highlight color">
              <Icon :icon="faHighlighter" size="lg" />
            </ColorPicker>
          </div>
        </div>

        <label for="themeBgImage">Background image</label>
        <div class="inline-flex flex-col gap-2">
          <span
            v-if="data.bg_image"
            class="w-36 aspect-video relative overflow-hidden rounded-md border border-k-fg-10"
          >
            <img :src="data.bg_image" alt="Background image" class="inset-0 object-cover">
            <button
              type="button"
              class="absolute inset-0 opacity-0 hover:opacity-100 bg-black/70 active:bg-black/85 active:text-[.9rem] transition-opacity"
              @click.prevent="data.bg_image = ''"
            >
              Remove
            </button>
          </span>
          <FileInput aria-label="Background image" accept="image/*" @change="onBackgroundImageChange" />
        </div>

        <label>Font</label>
        <div class="flex gap-2">
          <SelectBox v-model="data.font_family" aria-label="Font family" @click="onFontSelectBoxClick">
            <option value="">Default</option>
            <option v-for="name in availableFonts" :key="name" :value="name">{{ name }}</option>
          </SelectBox>
          <TextInput
            v-model="data.font_size"
            aria-label="Font size"
            class="!w-20"
            min="1"
            step="0.5"
            title="Font size"
            type="number"
          />
        </div>
      </div>
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn bordered transparent @click.prevent="previewing = true">Preview</Btn>
      <Btn transparent @click.prevent="maybeClose">Cancel</Btn>
    </footer>

    <Btn v-if="previewing" class="btn-exit-preview fixed right-4 top-3" @click.prevent="previewing = false">
      Exit preview
    </Btn>
  </form>
</template>

<script setup lang="ts">
import { faFillDrip, faFont, faHighlighter } from '@fortawesome/free-solid-svg-icons'
import { computed, onMounted, ref, watch } from 'vue'
import { logger } from '@/utils/logger'
import { useImageFileInput } from '@/composables/useImageFileInput'
import { useForm } from '@/composables/useForm'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { themeStore } from '@/stores/themeStore'
import type { ThemeData } from '@/stores/themeStore'
import { commonFonts, genericFonts } from '@/config/fonts'

import SelectBox from '@/components/ui/form/SelectBox.vue'
import FileInput from '@/components/ui/form/FileInput.vue'
import Btn from '@/components/ui/form/Btn.vue'
import ColorPicker from '@/components/ui/form/ColorPicker.vue'
import TextInput from '@/components/ui/form/TextInput.vue'

const props = defineProps<{ toggleCssClass: (...classes: string[]) => void }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const toggleCssClass = props.toggleCssClass

const close = () => emit('close')

const { showConfirmDialog } = useDialogBox()
const { toastSuccess } = useMessageToaster()

const style = window.getComputedStyle(document.body)

const { data, isPristine, handleSubmit } = useForm<ThemeData>({
  initialValues: {
    name: '',
    font_family: style.getPropertyValue('--font-family'),
    font_size: Number.parseFloat(style.getPropertyValue('--font-size')) || 13,
    bg_color: style.getPropertyValue('--color-bg'),
    fg_color: style.getPropertyValue('--color-fg'),
    highlight_color: style.getPropertyValue('--color-highlight'),
    bg_image: '',
  },
  onSubmit: async data => await themeStore.store(data),
  onSuccess: (theme: Theme) => {
    toastSuccess('Theme created.')
    themeStore.setTheme(theme)
    close()
  },
})

const previewing = ref(false)
const availableFonts = ref<string[]>([])

const hasLocalFontsApi = 'queryLocalFonts' in window
const systemFontsLoaded = ref(false)
const localFontsPermissionDenied = ref(false)

const shouldTryLoadSystemFonts = computed(() => {
  return hasLocalFontsApi && !systemFontsLoaded.value && !localFontsPermissionDenied.value
})

const loadCommonFonts = () => (availableFonts.value = genericFonts.concat(...commonFonts))

const tryLoadSystemFonts = async () => {
  try {
    // @ts-expect-error
    const localFonts: [{ family: string }] = await window.queryLocalFonts()
    availableFonts.value = genericFonts.concat(...[...new Set(localFonts.map(font => font.family))].sort())

    systemFontsLoaded.value = true
  } catch (err: unknown) {
    logger.error('Failed to load system fonts.', err)
    loadCommonFonts()
  }
}

const loadAvailableFonts = async () => shouldTryLoadSystemFonts.value
  ? await tryLoadSystemFonts()
  : loadCommonFonts()

const onFontSelectBoxClick = async () => {
  if (availableFonts.value.length === 0) {
    await loadAvailableFonts()
  }
}

const applyProperty = (property: string, value: string) => document.body.style.setProperty(property, value)

watch(previewing, () => toggleCssClass('backdrop:bg-transparent', 'bg-transparent', 'cursor-not-allowed'))
watch(() => data.fg_color, color => applyProperty('--color-fg', color))
watch(() => data.bg_color, color => applyProperty('--color-bg', color))
watch(() => data.highlight_color, color => applyProperty('--color-highlight', color))

watch(() => data.bg_image, imageUrl => applyProperty('--bg-image', imageUrl ? `url(${imageUrl})` : 'none'), {
  immediate: true,
})

watch(() => data.font_family, font => applyProperty('--font-family', font), { immediate: true })
watch(() => data.font_size, size => applyProperty('--font-size', `${size}px`))

const { onImageInputChange: onBackgroundImageChange } = useImageFileInput({
  onImageDataUrl: dataUrl => data.bg_image = dataUrl,
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog('Discard all changes?')) {
    // restore the theme
    themeStore.setTheme()
    close()
  }
}

onMounted(async () => {
  try {
    // @ts-expect-error 'local-fonts' is not yet supported in Firefox and Safari.
    const permission = await navigator.permissions.query({ name: 'local-fonts' })
    localFontsPermissionDenied.value = permission.state === 'denied'
  } catch (err: unknown) {
    window.RUNNING_UNIT_TESTS || logger.error('Failed to check local fonts permission.', err)
  }

  await loadAvailableFonts()
})
</script>

<style scoped lang="postcss">
label {
  @apply flex items-center;
}

form.previewing {
  @apply bg-transparent;
}

form.previewing > *:not(.btn-exit-preview) {
  opacity: 0;
}

.btn-exit-preview {
  filter: drop-shadow(0 0 10rem var(--color-bg));
}
</style>
