<template>
  <form data-testid="edit-user-form" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit User</h1>
    </header>

    <main class="space-y-5">
      <AlertBox v-if="user.sso_provider" type="info">
        This user logs in via SSO by {{ user.sso_provider }}.<br>
      </AlertBox>

      <FormRow>
        <template #label>Name</template>
        <TextInput v-model="data.name" v-koel-focus name="name" required />
      </FormRow>
      <FormRow>
        <template #label>Email</template>
        <TextInput
          v-model="data.email"
          :readonly="user.sso_provider"
          name="email"
          required
          title="Email"
          type="email"
        />
      </FormRow>
      <FormRow v-if="!user.sso_provider">
        <template #label>Password</template>
        <TextInput
          v-model="data.password"
          autocomplete="new-password"
          name="password"
          placeholder="Leave blank for no changes"
          type="password"
        />
        <template #help>Min. 10 characters. Should be a mix of characters, numbers, and symbols.</template>
      </FormRow>
      <RolePicker v-model="data.role" />
    </main>

    <footer>
      <Btn class="btn-update" type="submit">Update</Btn>
      <Btn class="btn-cancel" white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { pick } from 'lodash'
import type { UpdateUserData } from '@/stores/userStore'
import { userStore } from '@/stores/userStore'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useModal } from '@/composables/useModal'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import AlertBox from '@/components/ui/AlertBox.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import RolePicker from '@/components/user/RolePicker.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const close = () => emit('close')

const user = useModal<'EDIT_USER_FORM'>().getFromContext('user')

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
    toastSuccess('User profile updated.')
    close()
  },
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog('Discard all changes?')) {
    close()
  }
}
</script>
