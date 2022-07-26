<template>
  <div class="edit-user" @keydown.esc="maybeClose">
    <SoundBar v-if="loading"/>
    <form v-else class="user-edit" data-testid="edit-user-form" @submit.prevent="submit">
      <header>
        <h1>Edit User</h1>
      </header>

      <main>
        <div class="form-row">
          <label>
            Name
            <input v-model="updateData.name" v-koel-focus name="name" required title="Name" type="text">
          </label>
        </div>
        <div class="form-row">
          <label>
            Email
            <input v-model="updateData.email" name="email" required title="Email" type="email">
          </label>
        </div>
        <div class="form-row">
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
            <input v-model="updateData.is_admin" name="is_admin" type="checkbox"> User is an admin
            <TooltipIcon title="Admins can perform administrative tasks like managing users and uploading songs."/>
          </label>
        </div>
      </main>

      <footer>
        <Btn class="btn-update" type="submit">Update</Btn>
        <Btn class="btn-cancel" white @click.prevent="maybeClose">Cancel</Btn>
      </footer>
    </form>
  </div>
</template>

<script lang="ts" setup>
import { isEqual } from 'lodash'
import { reactive, ref, watch } from 'vue'
import { parseValidationError, requireInjection } from '@/utils'
import { UpdateUserData, userStore } from '@/stores'
import { DialogBoxKey, MessageToasterKey, UserKey } from '@/symbols'

import Btn from '@/components/ui/Btn.vue'
import SoundBar from '@/components/ui/SoundBar.vue'
import TooltipIcon from '@/components/ui/TooltipIcon.vue'

const toaster = requireInjection(MessageToasterKey)
const dialog = requireInjection(DialogBoxKey)
const [user] = requireInjection(UserKey)

let originalData: UpdateUserData
let updateData: UpdateUserData

watch(user, () => {
  originalData = {
    name: user.value.name,
    email: user.value.email,
    is_admin: user.value.is_admin
  }

  updateData = reactive(Object.assign({}, originalData))
}, { immediate: true })

const loading = ref(false)

const submit = async () => {
  loading.value = true

  try {
    await userStore.update(user.value, updateData)
    toaster.value.success('User profile updated.')
    close()
  } catch (err: any) {
    const msg = err.response.status === 422 ? parseValidationError(err.response.data)[0] : 'Unknown error.'
    dialog.value.error(msg, 'Error')
  } finally {
    loading.value = false
  }
}

const emit = defineEmits(['close'])

const close = () => emit('close')

const maybeClose = async () => {
  if (isEqual(originalData, updateData)) {
    close()
    return
  }

  await dialog.value.confirm('Discard all changes?') && close()
}
</script>

<style lang="scss" scoped>
.help {
  margin-top: .75rem;
}
</style>
