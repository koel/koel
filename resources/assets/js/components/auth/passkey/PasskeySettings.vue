<template>
  <SettingGroup>
    <template #title>Passkeys</template>
    <template #subtitle>
      Log in with your fingerprint, face, screen lock, or a security key instead of a password.
    </template>

    <ul v-if="passkeys.length" class="max-w-xl divide-y divide-k-fg-5" data-testid="passkey-list">
      <PasskeyListItem v-for="passkey in passkeys" :key="passkey.id" :passkey @removed="onRemoved" />
    </ul>

    <AddPasskeyForm v-if="adding" :has-passkeys="passkeys.length > 0" @added="onAdded" @cancel="adding = false" />
    <div v-else-if="supported">
      <Btn type="button" variant="ghost" bordered @click.prevent="adding = true">Add a Passkey</Btn>
    </div>
    <p v-else class="text-k-fg-70" data-testid="passkeys-unsupported">This browser doesn't support passkeys.</p>
  </SettingGroup>
</template>

<script lang="ts" setup>
import { onMounted, ref } from 'vue'
import { isPasskeySupported, passkeyService } from '@/services/passkeyService'
import { useMessageToaster } from '@/composables/useMessageToaster'

import AddPasskeyForm from '@/components/auth/passkey/AddPasskeyForm.vue'
import PasskeyListItem from '@/components/auth/passkey/PasskeyListItem.vue'
import Btn from '@/components/ui/form/Btn.vue'
import SettingGroup from '@/components/screens/settings/SettingGroup.vue'

const supported = isPasskeySupported()
const passkeys = ref<Passkey[]>([])
const adding = ref(false)

const { toastSuccess } = useMessageToaster()

const onAdded = (passkey: Passkey) => {
  passkeys.value.push(passkey)
  adding.value = false
  toastSuccess('Passkey added.')
}

const onRemoved = (removed: Passkey) => {
  passkeys.value = passkeys.value.filter(({ id }) => id !== removed.id)
}

onMounted(async () => {
  passkeys.value = await passkeyService.fetchAll()
})
</script>
