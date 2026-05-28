<template>
  <section>
    <h3 class="text-2xl mb-2">Subsonic API Key</h3>

    <p>
      Use this key to connect Subsonic-compatible clients (Symfonium, Feishin, substreamer, etc.) to your
      {{ appName }} library. <br />
      Configure the client with your email as the username and this key in the API-key or password field.
    </p>

    <div
      class="mt-4 w-full lg:w-1/2 relative text-k-fg-70 flex items-stretch border border-k-fg-10 overflow-hidden rounded-md bg-k-bg-50 focus-within:border-k-highlight transition-[border,background-color] duration-200 ease-in-out"
      data-testid="subsonic-credentials"
    >
      <TextInput
        id="subsonicApiKey"
        :model-value="key"
        :type="revealed ? 'text' : 'password'"
        readonly
        aria-label="Subsonic API key"
        class="flex-1 rounded-none border-0 focus-visible:outline-hidden px-4 font-mono read-only:!bg-transparent read-only:!text-current"
        @focus="onFocus"
      />
      <button
        type="button"
        class="px-3 hover:bg-k-fg-5"
        :title="revealed ? 'Hide key' : 'Reveal key'"
        @click.prevent="revealed = !revealed"
      >
        <EyeOffIcon v-if="revealed" :size="16" />
        <EyeIcon v-else :size="16" />
      </button>
      <button
        type="button"
        class="px-3 hover:bg-k-fg-5 border-l border-k-fg-10"
        :title="copied ? 'Copied' : 'Copy'"
        @click.prevent="copyKey"
      >
        <CopyIcon :size="16" />
      </button>
    </div>

    <div class="mt-4">
      <Btn variant="destructive" type="button" :disabled="regenerating" @click.prevent="regenerate">
        Regenerate Key
      </Btn>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { CopyIcon, EyeIcon, EyeOffIcon } from 'lucide-vue-next'
import { computed, defineAsyncComponent, onBeforeUnmount, ref } from 'vue'
import { userStore } from '@/stores/userStore'
import { useAuthorization } from '@/composables/useAuthorization'
import { useBranding } from '@/composables/useBranding'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { copyText } from '@/utils/helpers'
import { logger } from '@/utils/logger'

const Btn = defineAsyncComponent(() => import('@/components/ui/form/Btn.vue'))
const TextInput = defineAsyncComponent(() => import('@/components/ui/form/TextInput.vue'))

const { currentUser } = useAuthorization()
const { name: appName } = useBranding()
const { showConfirmDialog } = useDialogBox()
const { toastSuccess, toastWarning } = useMessageToaster()

const copied = ref(false)
const regenerating = ref(false)
const revealed = ref(false)

const key = computed(() => currentUser.value.subsonic_api_key)

let copiedResetTimer: ReturnType<typeof window.setTimeout> | null = null

const clearCopiedResetTimer = () => {
  if (copiedResetTimer !== null) {
    window.clearTimeout(copiedResetTimer)
    copiedResetTimer = null
  }
}

const onFocus = (event: FocusEvent) => (event.target as HTMLInputElement).select()

const copyKey = async () => {
  await copyText(key.value)
  copied.value = true
  toastSuccess('Subsonic API key copied to clipboard.')

  clearCopiedResetTimer()
  copiedResetTimer = window.setTimeout(() => {
    copied.value = false
    copiedResetTimer = null
  }, 2000)
}

const regenerate = async () => {
  const confirmed = await showConfirmDialog(
    'Regenerate Subsonic API key? Any client using the old key will stop working until reconfigured.',
  )

  if (!confirmed) {
    return
  }

  regenerating.value = true

  try {
    await userStore.regenerateSubsonicApiKey()
    toastSuccess('Subsonic API key regenerated.')
  } catch (error: unknown) {
    logger.error(error)
    toastWarning('Failed to regenerate Subsonic API key.')
  } finally {
    regenerating.value = false
  }
}

onBeforeUnmount(clearCopiedResetTimer)
</script>
