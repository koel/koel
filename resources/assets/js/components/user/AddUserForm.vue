<template>
  <div @keydown.esc="maybeClose">
    <SoundBars v-if="loading"/>
    <form v-else data-testid="add-user-form" @submit.prevent="submit">
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
            <CheckBox name="is_admin" v-model="newUser.is_admin"/>
            User is an admin
            <TooltipIcon title="Admins can perform administrative tasks like managing users and uploading songs."/>
          </label>
        </div>
      </main>

      <footer>
        <Btn class="btn-add" type="submit">Save</Btn>
        <Btn class="btn-cancel" white @click.prevent="maybeClose">Cancel</Btn>
      </footer>
    </form>
  </div>
</template>

<script lang="ts" setup>
import { isEqual } from 'lodash'
import { reactive, ref } from 'vue'
import { CreateUserData, userStore } from '@/stores'
import { parseValidationError, requireInjection } from '@/utils'
import { DialogBoxKey, MessageToasterKey } from '@/symbols'

import Btn from '@/components/ui/Btn.vue'
import SoundBars from '@/components/ui/SoundBars.vue'
import TooltipIcon from '@/components/ui/TooltipIcon.vue'
import CheckBox from '@/components/ui/CheckBox.vue'

const toaster = requireInjection(MessageToasterKey)
const dialog = requireInjection(DialogBoxKey)

const loading = ref(false)

const emptyUserData: CreateUserData = {
  name: '',
  email: '',
  password: '',
  is_admin: false
}

const newUser = reactive<CreateUserData>(Object.assign({}, emptyUserData))

const submit = async () => {
  loading.value = true

  try {
    await userStore.store(newUser)
    toaster.value.success(`New user "${newUser.name}" created.`)
    close()
  } catch (err: any) {
    const msg = err.response.status === 422 ? parseValidationError(err.response.data)[0] : 'Unknown error.'
    dialog.value.error(msg, 'Error')
  } finally {
    loading.value = false
  }
}

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const maybeClose = async () => {
  if (isEqual(newUser, emptyUserData)) {
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
