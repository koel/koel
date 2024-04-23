<template>
  <form @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <h1>Add New User</h1>
    </header>

    <main class="space-y-5">
      <FormRow>
        <template #label>Name</template>
        <TextInput v-model="newUser.name" v-koel-focus name="name" required title="Name" />
      </FormRow>
      <FormRow>
        <template #label>Email</template>
        <TextInput v-model="newUser.email" name="email" required title="Email" type="email" />
      </FormRow>
      <FormRow>
        <template #label>Password</template>
        <TextInput
          v-model="newUser.password"
          autocomplete="new-password"
          name="password"
          required
          title="Password"
          type="password"
        />
        <template #help>Min. 10 characters. Should be a mix of characters, numbers, and symbols.</template>
      </FormRow>
      <FormRow>
        <div>
          <CheckBox v-model="newUser.is_admin" name="is_admin" /> User is an admin
          <TooltipIcon title="Admins can perform administrative tasks like managing users and uploading songs." />
        </div>
      </FormRow>
    </main>

    <footer>
      <Btn class="btn-add" type="submit">Save</Btn>
      <Btn class="btn-cancel" white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { isEqual } from 'lodash'
import { reactive } from 'vue'
import { CreateUserData, userStore } from '@/stores'
import { useDialogBox, useErrorHandler, useMessageToaster, useOverlay } from '@/composables'

import Btn from '@/components/ui/form/Btn.vue'
import TooltipIcon from '@/components/ui/TooltipIcon.vue'
import CheckBox from '@/components/ui/form/CheckBox.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const emptyUserData: CreateUserData = {
  name: '',
  email: '',
  password: '',
  is_admin: false
}

const newUser = reactive<CreateUserData>(Object.assign({}, emptyUserData))

const submit = async () => {
  showOverlay()

  try {
    await userStore.store(newUser)
    toastSuccess(`New user "${newUser.name}" created.`)
    close()
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
  } finally {
    hideOverlay()
  }
}

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const maybeClose = async () => {
  if (isEqual(newUser, emptyUserData)) {
    close()
    return
  }

  await showConfirmDialog('Discard all changes?') && close()
}
</script>
