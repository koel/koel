<template>
  <section>
    <h3 class="text-2xl mb-2 flex items-center gap-2">
      <img :src="listenbrainzLogo" alt="ListenBrainz Logo" height="20" width="20" />
      ListenBrainz
    </h3>

    <div v-if="connected" data-testid="listenbrainz-connected">
      <p>Your ListenBrainz account is connected. {{ appName }} will submit your listens as you play.</p>
      <div class="mt-4">
        <Btn type="button" variant="destructive" @click.prevent="disconnect">Disconnect</Btn>
      </div>
    </div>

    <form v-else data-testid="listenbrainz-form" @submit.prevent="handleSubmit">
      <p>
        Connect your ListenBrainz account to submit your listens as you play. Grab your user token from your
        <a class="text-k-highlight" href="https://listenbrainz.org/settings/" rel="noopener" target="_blank">
          ListenBrainz settings
        </a>
        and paste it below.
      </p>
      <div class="mt-4 flex items-start gap-2 lg:w-1/2">
        <div class="flex-1">
          <PasswordField
            v-model="data.token"
            autocomplete="off"
            name="token"
            placeholder="ListenBrainz user token"
            required
          />
        </div>
        <Btn type="submit">Connect</Btn>
      </div>
    </form>
  </section>
</template>

<script lang="ts" setup>
import listenbrainzLogo from '@/../img/logos/listenbrainz.svg'

import { computed } from 'vue'
import { http } from '@/services/http'
import { useAuthorization } from '@/composables/useAuthorization'
import { useBranding } from '@/composables/useBranding'
import { useForm } from '@/composables/useForm'
import { useMessageToaster } from '@/composables/useMessageToaster'

import Btn from '@/components/ui/form/Btn.vue'
import PasswordField from '@/components/ui/form/PasswordField.vue'

const { currentUser } = useAuthorization()
const { name: appName } = useBranding()
const { toastSuccess } = useMessageToaster()

const connected = computed(() => Boolean(currentUser.value.preferences.listenbrainz_token))

const { data, handleSubmit } = useForm<{ token: string }>({
  initialValues: { token: '' },
  onSubmit: async ({ token }) => {
    await http.post('listenbrainz/token', { token })
    return token
  },
  onSuccess: (token: string) => {
    currentUser.value.preferences.listenbrainz_token = token
    toastSuccess('ListenBrainz account connected.')
  },
})

const disconnect = async () => {
  await http.delete('listenbrainz/disconnect')
  currentUser.value.preferences.listenbrainz_token = undefined
  toastSuccess('ListenBrainz account disconnected.')
}
</script>
