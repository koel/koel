<template>
  <section>
    <h3 class="text-2xl mb-2">Subsonic API Key</h3>

    <p>
      Use this key to connect Subsonic-compatible clients (Symfonium, Feishin, substreamer, etc.) to your
      {{ appName }} library. <br />
      Configure the client with your email as the username and this key in the API-key or password field.
    </p>

    <div class="mt-4 space-y-2" data-testid="subsonic-credentials">
      <label class="flex items-stretch gap-2 w-full lg:w-1/2">
        <TextInput id="subsonicApiKey" :model-value="key" readonly class="flex-1 font-mono" @focus="onFocus" />
        <Btn variant="ghost" type="button" :title="copied ? 'Copied' : 'Copy'" @click.prevent="copyKey">
          <CopyIcon :size="16" />
        </Btn>
      </label>
    </div>

    <div class="mt-4">
      <Btn variant="destructive" type="button" :disabled="regenerating" @click.prevent="regenerate">
        Regenerate Key
      </Btn>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { CopyIcon } from 'lucide-vue-next'
import { computed, defineAsyncComponent, ref } from 'vue'
import { userStore } from '@/stores/userStore'
import { useAuthorization } from '@/composables/useAuthorization'
import { useBranding } from '@/composables/useBranding'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { copyText } from '@/utils/helpers'

const Btn = defineAsyncComponent(() => import('@/components/ui/form/Btn.vue'))
const TextInput = defineAsyncComponent(() => import('@/components/ui/form/TextInput.vue'))

const { currentUser } = useAuthorization()
const { name: appName } = useBranding()
const { showConfirmDialog } = useDialogBox()
const { toastSuccess, toastWarning } = useMessageToaster()

const copied = ref(false)
const regenerating = ref(false)

const key = computed(() => currentUser.value.subsonic_api_key)

const onFocus = (event: FocusEvent) => (event.target as HTMLInputElement).select()

const copyKey = async () => {
  await copyText(key.value)
  copied.value = true
  toastSuccess('Subsonic API key copied to clipboard.')
  window.setTimeout(() => (copied.value = false), 2000)
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
    toastWarning('Failed to regenerate Subsonic API key.')
    throw error
  } finally {
    regenerating.value = false
  }
}
</script>
