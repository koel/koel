<template>
  <article class="w-[650px]" :class="(loading || encryptingOptions) && 'pointer-events-none opacity-70'">
    <header>
      <h1>{{ t('embeds.embed', { type: typeLabel }) }}</h1>
    </header>
    <main>
      <EmbedOptionsPanel v-model="options" class="mb-5" />

      <iframe
        v-if="embedSrc"
        ref="previewIframe"
        :height="options.layout === 'compact' ? '150' : 350"
        :src="embedSrc"
        allow="autoplay; encrypted-media"
        class="rounded-xl bg-transparent w-full max-w-[650px] overflow-hidden border-0"
        data-testid="embed-preview-iframe"
        loading="lazy"
      />

      <div
        v-show="showCode"
        class="mt-5 px-4 py-2 font-mono break-all max-h-32 bg-k-fg-5 rounded-md cursor-pointer select-all overflow-auto"
        data-testid="embed-code"
        @click="copyCode"
      >
        {{ code }}
      </div>
    </main>
    <footer class="flex items-center">
      <div class="flex-1">
        <Btn primary type="submit" @click.prevent="copyCode">{{ t('embeds.copyCode') }}</Btn>
        <Btn white @click="emit('close')">{{ t('playlists.close') }}</Btn>
      </div>
      <label>
        <CheckBox v-model="showCode" />
        {{ t('embeds.showCode') }}
      </label>
    </footer>
  </article>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
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

const props = defineProps<{ embeddable: Embeddable }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const { embeddable } = props

const { t } = useI18n()
const { isPlus } = useKoelPlus()
const { toastSuccess } = useMessageToaster()
const { handleHttpError } = useErrorHandler()

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
    + ` height="${options.layout === 'compact' ? '150' : 350}" frameborder="0" allow="autoplay; encrypted-media"`
    + ' loading="lazy"></iframe>'
})

watch(embedSrc, value => {
  if (!previewIframe.value?.contentWindow) {
    return
  }

  previewIframe.value.contentWindow.location.replace(String(value))
  previewIframe.value.contentWindow.location.reload()
})

const typeLabel = computed(() => {
  switch (embeddable.type) {
    case 'albums':
      return t('embeds.types.album')
    case 'artists':
      return t('embeds.types.artist')
    case 'playlists':
      return t('embeds.types.playlist')
    case 'songs':
      return t('embeds.types.song')
    case 'episodes':
      return t('embeds.types.podcastEpisode')
    default:
      throw new Error('Unknown embeddable type')
  }
})

const copyCode = async () => {
  await copyText(code.value)
  toastSuccess(t('embeds.codeCopied'))
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
