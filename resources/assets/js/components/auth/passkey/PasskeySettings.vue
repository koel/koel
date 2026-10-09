<template>
  <SettingGroup>
    <template #title>Passkeys</template>
    <template #subtitle>
      Log in with your fingerprint, face, screen lock, or a security key instead of a password.
    </template>

    <ul v-if="passkeys.length" class="max-w-xl divide-y divide-k-fg-5" data-testid="passkey-list">
      <PasskeyListItem v-for="passkey in passkeys" :key="passkey.id" :passkey @removed="onRemoved" />
    </ul>

    <div v-if="supported">
      <Btn type="button" variant="ghost" bordered @click.prevent="openAddPasskeyForm">Add a Passkey</Btn>
    </div>
    <p v-else class="text-k-fg-70" data-testid="passkeys-unsupported">This browser doesn't support passkeys.</p>
  </SettingGroup>
</template>

<script lang="ts" setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { isPasskeySupported, passkeyService } from '@/services/passkeyService'
import { eventBus } from '@/utils/eventBus'
import { defineAsyncComponent } from '@/utils/helpers'
import { useModal } from '@/composables/useModal'

import PasskeyListItem from '@/components/auth/passkey/PasskeyListItem.vue'
import Btn from '@/components/ui/form/Btn.vue'
import SettingGroup from '@/components/screens/settings/SettingGroup.vue'

const supported = isPasskeySupported()
const passkeys = ref<Passkey[]>([])

const AddPasskeyForm = defineAsyncComponent(() => import('@/components/auth/passkey/AddPasskeyForm.vue'))

const { openModal } = useModal()

const openAddPasskeyForm = () =>
  openModal<'ADD_PASSKEY_FORM'>(AddPasskeyForm, { hasPasskeys: passkeys.value.length > 0 })

const onRemoved = (removed: Passkey) => {
  passkeys.value = passkeys.value.filter(({ id }) => id !== removed.id)
}

const showAddedPasskey = (passkey: Passkey) => passkeys.value.push(passkey)

eventBus.on('PASSKEY_ADDED', showAddedPasskey)

onBeforeUnmount(() => eventBus.off('PASSKEY_ADDED', showAddedPasskey))

onMounted(async () => {
  passkeys.value = await passkeyService.fetchAll()
})
</script>
