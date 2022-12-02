<template>
  <form @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <h1>Add New User</h1>
    </header>

    <main>
      <div class="form-row">
        <label>
          Name
          <input v-model="newUser.name" v-koel-focus name="name" required title="Name" type="text">
        </label>
      </div>
      <div class="form-row">
        <label>
          Email
          <input v-model="newUser.email" name="email" required title="Email" type="email">
        </label>
      </div>
      <div class="form-row">
        <label>
          Password
          <input
            v-model="newUser.password"
            autocomplete="new-password"
            name="password"
            required
            title="Password"
            type="password"
          >
        </label>
        <p class="help">Min. 10 characters. Should be a mix of characters, numbers, and symbols.</p>
      </div>
      <div class="form-row">
        <label>
          <CheckBox v-model="newUser.is_admin" name="is_admin" />
          User is an admin
          <TooltipIcon title="Admins can perform administrative tasks like managing users and uploading songs." />
        </label>
      </div>
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
import { parseValidationError } from '@/utils'
import { useDialogBox, useMessageToaster, useOverlay } from '@/composables'

import Btn from '@/components/ui/Btn.vue'
import TooltipIcon from '@/components/ui/TooltipIcon.vue'
import CheckBox from '@/components/ui/CheckBox.vue'

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showErrorDialog, showConfirmDialog } = useDialogBox()

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
  } catch (err: any) {
    const msg = err.response.status === 422 ? parseValidationError(err.response.data)[0] : 'Unknown error.'
    showErrorDialog(msg, 'Error')
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

<style lang="scss" scoped>
.help {
  margin-top: .75rem;
}
</style>
