<template>
  <SettingGroup>
    <template #title>Passkeys</template>
    <template #subtitle>
      Log in with your fingerprint, face, screen lock, or a security key instead of a password.
    </template>

    <ul v-if="passkeys.length" class="max-w-xl divide-y divide-k-fg-5" data-testid="passkey-list">
      <li v-for="passkey in passkeys" :key="passkey.id" class="flex items-center gap-3 py-2.5">
        <div class="flex-1 min-w-0">
          <p class="truncate text-k-fg">{{ passkey.name }}</p>
          <p class="text-sm text-k-fg-50">
            {{ describe(passkey) }}
          </p>
        </div>
        <Btn size="small" variant="ghost" bordered @click.prevent="remove(passkey)">Remove</Btn>
      </li>
    </ul>

    <AddPasskeyForm v-if="adding" @added="onAdded" @cancel="adding = false" />
    <div v-else-if="supported">
      <Btn type="button" variant="ghost" bordered @click.prevent="adding = true">Add a Passkey</Btn>
    </div>
    <p v-else class="text-k-fg-70" data-testid="passkeys-unsupported">This browser doesn't support passkeys.</p>
  </SettingGroup>
</template>

<script lang="ts" setup>
import { formatTimeAgo } from '@vueuse/core'
import { onMounted, ref } from 'vue'
import { isPasskeySupported, passkeyService } from '@/services/passkeyService'
import { useDialogBox } from '@/composables/useDialogBox'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useMessageToaster } from '@/composables/useMessageToaster'

import AddPasskeyForm from '@/components/auth/passkey/AddPasskeyForm.vue'
import Btn from '@/components/ui/form/Btn.vue'
import SettingGroup from '@/components/screens/settings/SettingGroup.vue'

const supported = isPasskeySupported()
const passkeys = ref<Passkey[]>([])
const adding = ref(false)

const { showConfirmDialog } = useDialogBox()
const { toastSuccess } = useMessageToaster()

const describe = (passkey: Passkey) => {
  const lastUsed = passkey.last_used_at ? `Last used ${formatTimeAgo(new Date(passkey.last_used_at))}` : 'Not used yet'

  return passkey.authenticator ? `${passkey.authenticator} · ${lastUsed}` : lastUsed
}

const onAdded = (passkey: Passkey) => {
  passkeys.value.push(passkey)
  adding.value = false
  toastSuccess('Passkey added.')
}

const remove = async (passkey: Passkey) => {
  if (!(await showConfirmDialog(`Remove the passkey "${passkey.name}"?`))) {
    return
  }

  try {
    await passkeyService.remove(passkey)
    passkeys.value = passkeys.value.filter(({ id }) => id !== passkey.id)
    toastSuccess('Passkey removed.')
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
  }
}

onMounted(async () => {
  passkeys.value = await passkeyService.fetchAll()
})
</script>
