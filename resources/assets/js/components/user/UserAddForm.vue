<template>
  <div class="add-user" @keydown.esc="maybeClose">
    <SoundBar v-if="loading"/>
    <form v-else class="user-add" data-testid="add-user-form" @submit.prevent="submit">
      <header>
        <h1>Add New User</h1>
      </header>

      <div>
        <div class="form-row">
          <label>Name</label>
          <input v-model="newUser.name" v-koel-focus name="name" required title="Name" type="text">
        </div>
        <div class="form-row">
          <label>Email</label>
          <input v-model="newUser.email" name="email" required title="Email" type="email">
        </div>
        <div class="form-row">
          <label>Password</label>
          <input
            v-model="newUser.password"
            autocomplete="new-password"
            name="password"
            required
            title="Password"
            type="password"
          >
          <p class="help">Min. 10 characters. Should be a mix of characters, numbers, and symbols.</p>
        </div>
        <div class="form-row">
          <label>
            <input v-model="newUser.is_admin" name="isAdmin" type="checkbox"> User is an admin
            <TooltipIcon title="Admins can perform administrative tasks like managing users and uploading songs."/>
          </label>
        </div>
      </div>

      <footer>
        <Btn class="btn-add" type="submit">Save</Btn>
        <Btn class="btn-cancel" white @click.prevent="maybeClose">Cancel</Btn>
      </footer>
    </form>
  </div>
</template>

<script lang="ts" setup>
import { isEqual } from 'lodash'
import { defineAsyncComponent, reactive, ref } from 'vue'
import { CreateUserData, userStore } from '@/stores'
import { alerts, parseValidationError } from '@/utils'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))
const SoundBar = defineAsyncComponent(() => import('@/components/ui/SoundBar.vue'))
const TooltipIcon = defineAsyncComponent(() => import('@/components/ui/TooltipIcon.vue'))

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
    alerts.success(`New user "${newUser.name}" created.`)
    close()
  } catch (err: any) {
    const msg = err.response.status === 422 ? parseValidationError(err.response.data)[0] : 'Unknown error.'
    alerts.error(msg)
  } finally {
    loading.value = false
  }
}

const emit = defineEmits(['close'])

const close = () => emit('close')

const maybeClose = () => {
  if (isEqual(newUser, emptyUserData)) {
    close()
    return
  }

  alerts.confirm('Discard all changes?', close)
}
</script>

<style lang="scss" scoped>
.help {
  margin-top: .75rem;
}
</style>
