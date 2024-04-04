<template>
  <form data-testid="edit-user-form" @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit User</h1>
    </header>

    <main>
      <AlertBox v-if="user.sso_provider" type="info">
        This user logs in via SSO by {{ user.sso_provider }}.<br>
      </AlertBox>

      <div class="form-row">
        <label>
          Name
          <input v-model="updateData.name" v-koel-focus name="name" required title="Name" type="text">
        </label>
      </div>
      <div class="form-row">
        <label>
          Email
          <input
            v-model="updateData.email"
            :readonly="user.sso_provider"
            name="email"
            required
            title="Email"
            type="email"
          >
        </label>
      </div>
      <div v-if="!user.sso_provider" class="form-row">
        <label>
          Password
          <input
            v-model="updateData.password"
            autocomplete="new-password"
            name="password"
            placeholder="Leave blank for no changes"
            type="password"
          >
        </label>
        <p class="help">Min. 10 characters. Should be a mix of characters, numbers, and symbols.</p>
      </div>
      <div class="form-row">
        <label>
          <CheckBox v-model="updateData.is_admin" name="is_admin" />
          User is an admin
          <TooltipIcon title="Admins can perform administrative tasks like managing users and uploading songs." />
        </label>
      </div>
    </main>

    <footer>
      <Btn class="btn-update" type="submit">Update</Btn>
      <Btn class="btn-cancel" white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { isEqual } from 'lodash'
import { reactive, watch } from 'vue'
import { logger, parseValidationError } from '@/utils'
import { UpdateUserData, userStore } from '@/stores'
import { useDialogBox, useMessageToaster, useModal, useOverlay } from '@/composables'

import Btn from '@/components/ui/Btn.vue'
import TooltipIcon from '@/components/ui/TooltipIcon.vue'
import CheckBox from '@/components/ui/CheckBox.vue'
import AlertBox from '@/components/ui/AlertBox.vue'

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog, showErrorDialog } = useDialogBox()
const user = useModal().getFromContext<User>('user')

let originalData: UpdateUserData
let updateData: UpdateUserData

watch(user, () => {
  originalData = {
    name: user.name,
    email: user.email,
    is_admin: user.is_admin
  }

  updateData = reactive(Object.assign({}, originalData))
}, { immediate: true })

const submit = async () => {
  showOverlay()

  try {
    await userStore.update(user, updateData)
    toastSuccess('User profile updated.')
    close()
  } catch (error: any) {
    const msg = error.response.status === 422 ? parseValidationError(error.response.data)[0] : 'Unknown error.'
    showErrorDialog(msg, 'Error')
    logger.error(error)
  } finally {
    hideOverlay()
  }
}

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const maybeClose = async () => {
  if (isEqual(originalData, updateData)) {
    close()
    return
  }

  await showConfirmDialog('Discard all changes?') && close()
}
</script>

<style lang="postcss" scoped>
.help {
  margin-top: .75rem;
}
</style>
