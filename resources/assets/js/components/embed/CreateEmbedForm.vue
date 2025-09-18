<template>
  <article class="w-[650px]" :class="(loading || encryptingOptions) && 'pointer-events-none opacity-70'">
    <header>
      <h1>Embed {{ typeLabel }}</h1>
    </header>
    <main>
      <EmbedOptionsPanel v-model="options" class="mb-5" />

      <iframe
        v-if="embedSrc"
        ref="previewIframe"
        :height="options.layout === 'compact' ? '150' : 350"
        :src="embedSrc"
        allow="autoplay *; encrypted-media *;"
        class="rounded-xl bg-transparent w-full max-w-[650px] overflow-hidden border-0"
        data-testid="embed-preview-iframe"
        loading="lazy"
      />

      <div
        v-show="showCode"
        class="mt-5 px-4 py-2 font-mono break-all max-h-32 bg-white/5 rounded-md cursor-pointer select-all overflow-auto"
        data-testid="embed-code"
        @click="copyCode"
      >
        {{ code }}
      </div>
    </main>
    <footer class="flex items-center">
      <div class="flex-1">
        <Btn primary type="submit" @click.prevent="copyCode">Copy Code</Btn>
        <Btn white @click="emit('close')">Close</Btn>
      </div>
      <label>
        <CheckBox v-model="showCode" />
        Show code
      </label>
    </footer>
  </article>
</template>

<script setup lang="ts">
import { useModal } from '@/composables/useModal'
import { computed, onMounted, ref, watch } from 'vue'
import { embedService } from '@/stores/embedService'
import { themeStore } from '@/stores/themeStore'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { copyText } from '@/utils/helpers'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import EmbedOptionsPanel from '@/components/embed/EmbedOptionsPanel.vue'
import CheckBox from '@/components/ui/form/CheckBox.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { isPlus } = useKoelPlus()
const { toastSuccess } = useMessageToaster()
const { handleHttpError } = useErrorHandler()
const { getFromContext } = useModal<'CREATE_EMBED_FORM'>()

const embeddable = getFromContext('embeddable')

const previewIframe = ref<HTMLIFrameElement>()
const embed = ref<Embed>()
const showCode = ref(false)
const loading = ref(false)
const encryptedOptions = ref('')

const { data: options, handleSubmit: encryptOptions, loading: encryptingOptions } = useForm<EmbedOptions>({
  initialValues: {
    theme: isPlus.value ? themeStore.getCurrentTheme().id : themeStore.getDefaultTheme().id,
    layout: ['songs', 'episodes'].includes(embeddable.type) ? 'compact' : 'full',
    preview: false,
  },
  onSubmit: async data => encryptedOptions.value = await embedService.encryptOptions(data),
  useOverlay: false,
})

watch(options, async () => await encryptOptions(), { immediate: true })

const embedSrc = computed(() => {
  if (!embed.value || !encryptedOptions.value) {
    return null
  }

  return `${window.BASE_URL}#/embed/${embed.value.id}/${encryptedOptions.value}`
})

const code = computed(() => {
  if (!embed.value) {
    return ''
  }

  return `<iframe src="${embedSrc.value}" style="border-radius:10px;width:100%;max-width:650px;overflow:hidden;"`
    + ` height="${options.layout === 'compact' ? '150' : 350}" frameborder="0" allow="autoplay *;encrypted-media *;"`
    + ' loading="lazy"></iframe>'
})

watch(embedSrc, value => {
  if (!previewIframe.value?.contentWindow) {
    return
  }

  previewIframe.value.contentWindow.location.replace(String(value))
  previewIframe.value.contentWindow.location.reload()
})

let typeLabel = ''

switch (embeddable.type) {
  case 'albums':
    typeLabel = 'Album'
    break
  case 'artists':
    typeLabel = 'Artist'
    break
  case 'playlists':
    typeLabel = 'Playlist'
    break
  case 'songs':
    typeLabel = 'Song'
    break
  case 'episodes':
    typeLabel = 'Podcast Episode'
    break
  default:
    throw new Error('Unknown embeddable type')
}

const copyCode = async () => {
  await copyText(code.value)
  toastSuccess('Code copied to clipboard.')
}

const resolveEmbed = async () => {
  loading.value = true

  try {
    embed.value = await embedService.resolveForEmbeddable(embeddable)
  } catch (e: unknown) {
    handleHttpError(e)
  } finally {
    loading.value = false
  }
}

onMounted(async () => await resolveEmbed())
</script>

<style scoped lang="postcss">
article {
  min-width: 550px;
}
</style>
