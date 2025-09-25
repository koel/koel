<template>
  <form @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Add New User</h1>
    </header>

    <main class="space-y-5">
      <FormRow>
        <template #label>Name</template>
        <TextInput v-model="data.name" v-koel-focus name="name" required />
      </FormRow>
      <FormRow>
        <template #label>Email</template>
        <TextInput v-model="data.email" name="email" required type="email" />
      </FormRow>
      <FormRow>
        <template #label>Password</template>
        <TextInput
          v-model="data.password"
          autocomplete="new-password"
          name="password"
          required
          title="Password"
          type="password"
        />
        <template #help>Min. 10 characters. Should be a mix of characters, numbers, and symbols.</template>
      </FormRow>
      <RolePicker v-model="data.role" />
    </main>

    <footer>
      <Btn :disabled="loading" class="btn-add" type="submit">Save</Btn>
      <Btn :disabled="loading" class="btn-cancel" white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import type { CreateUserData } from '@/stores/userStore'
import { userStore } from '@/stores/userStore'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import RolePicker from '@/components/user/RolePicker.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const close = () => emit('close')

const { data, isPristine, loading, handleSubmit } = useForm<CreateUserData>({
  initialValues: {
    name: '',
    email: '',
    password: '',
    role: 'user',
  },
  onSubmit: async data => await userStore.store(data),
  onSuccess: (user: User) => {
    toastSuccess(`New user "${user.name}" created.`)
    close()
  },
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog('Discard all changes?')) {
    close()
  }
}
</script>
