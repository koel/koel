<template>
  <form data-testid="edit-user-form" @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit User</h1>
    </header>

    <main class="space-y-5">
      <AlertBox v-if="user.sso_provider" type="info">
        This user logs in via SSO by {{ user.sso_provider }}.<br>
      </AlertBox>

      <FormRow>
        <template #label>Name</template>
        <TextInput v-model="updateData.name" v-koel-focus name="name" required title="Name" />
      </FormRow>
      <FormRow>
        <template #label>Email</template>
        <TextInput
          v-model="updateData.email"
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
          v-model="updateData.password"
          autocomplete="new-password"
          name="password"
          placeholder="Leave blank for no changes"
          title="Password"
          type="password"
        />
        <template #help>Min. 10 characters. Should be a mix of characters, numbers, and symbols.</template>
      </FormRow>
      <FormRow>
        <div>
          <CheckBox v-model="updateData.is_admin" name="is_admin" />
          User is an admin
          <TooltipIcon title="Admins can perform administrative tasks like managing users and uploading songs." />
        </div>
      </FormRow>
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
import { UpdateUserData, userStore } from '@/stores'
import { useDialogBox, useErrorHandler, useMessageToaster, useModal, useOverlay } from '@/composables'

import Btn from '@/components/ui/form/Btn.vue'
import TooltipIcon from '@/components/ui/TooltipIcon.vue'
import CheckBox from '@/components/ui/form/CheckBox.vue'
import AlertBox from '@/components/ui/AlertBox.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

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
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
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
