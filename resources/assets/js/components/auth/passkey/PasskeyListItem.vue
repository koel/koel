<template>
  <li class="flex items-center gap-3 py-2.5">
    <div class="flex-1 min-w-0">
      <p class="truncate text-k-fg">{{ passkey.name }}</p>
      <p class="text-sm text-k-fg-50">{{ description }}</p>
    </div>
    <Btn size="small" variant="ghost" bordered @click.prevent="remove">Remove</Btn>
  </li>
</template>

<script lang="ts" setup>
import { formatTimeAgo } from '@vueuse/core'
import { computed } from 'vue'
import { passkeyService } from '@/services/passkeyService'
import { useDialogBox } from '@/composables/useDialogBox'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useMessageToaster } from '@/composables/useMessageToaster'

import Btn from '@/components/ui/form/Btn.vue'

const props = defineProps<{ passkey: Passkey }>()
const emit = defineEmits<{ (e: 'removed', passkey: Passkey): void }>()

const { showConfirmDialog } = useDialogBox()
const { toastSuccess } = useMessageToaster()
const { handleHttpError } = useErrorHandler('dialog')

const description = computed(() => {
  const lastUsed = props.passkey.last_used_at
    ? `Last used ${formatTimeAgo(new Date(props.passkey.last_used_at))}`
    : 'Not used yet'

  return props.passkey.authenticator ? `${props.passkey.authenticator} · ${lastUsed}` : lastUsed
})

const remove = async () => {
  if (!(await showConfirmDialog(`Remove the passkey "${props.passkey.name}"?`))) {
    return
  }

  try {
    await passkeyService.remove(props.passkey)
    toastSuccess('Passkey removed.')
    emit('removed', props.passkey)
  } catch (error: unknown) {
    handleHttpError(error)
  }
}
</script>
