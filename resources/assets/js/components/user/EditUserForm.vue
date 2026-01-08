<template>
  <form data-testid="edit-user-form" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>{{ t('users.edit') }}</h1>
    </header>

    <main class="space-y-5">
      <AlertBox v-if="user.sso_provider" type="info">
        {{ t('users.ssoLogin', { provider: user.sso_provider }) }}<br>
      </AlertBox>

      <FormRow>
        <template #label>{{ t('users.name') }}</template>
        <TextInput v-model="data.name" v-koel-focus name="name" required />
      </FormRow>
      <FormRow>
        <template #label>{{ t('users.email') }}</template>
        <TextInput
          v-model="data.email"
          :readonly="user.sso_provider"
          name="email"
          required
          :title="t('users.email')"
          type="email"
        />
      </FormRow>
      <FormRow v-if="!user.sso_provider">
        <template #label>{{ t('users.password') }}</template>
        <TextInput
          v-model="data.password"
          autocomplete="new-password"
          name="password"
          :placeholder="t('preferences.leaveEmpty')"
          type="password"
        />
        <template #help>{{ t('users.passwordRequirements') }}</template>
      </FormRow>
      <RolePicker v-model="data.role" />
    </main>

    <footer>
      <Btn class="btn-update" type="submit">{{ t('users.update') }}</Btn>
      <Btn class="btn-cancel" white @click.prevent="maybeClose">{{ t('auth.cancel') }}</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { useI18n } from 'vue-i18n'
import { pick } from 'lodash'
import type { UpdateUserData } from '@/stores/userStore'
import { userStore } from '@/stores/userStore'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import AlertBox from '@/components/ui/AlertBox.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import RolePicker from '@/components/user/RolePicker.vue'

const props = defineProps<{ user: User }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const { user } = props
const { t } = useI18n()

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const close = () => emit('close')

const { data, isPristine, handleSubmit } = useForm<UpdateUserData>({
  initialValues: {
    ...pick(user, 'name', 'email', 'role'),
    password: '',
  },
  onSubmit: async data => {
    const formattedData = { ...data }

    if (!formattedData.password) {
      delete formattedData.password
    }

    await userStore.update(user, formattedData)
  },
  onSuccess: () => {
    toastSuccess(t('users.updated'))
    close()
  },
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog(t('playlists.discardChanges'))) {
    close()
  }
}
</script>
