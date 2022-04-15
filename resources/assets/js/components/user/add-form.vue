<template>
  <div class="add-user" @keydown.esc="maybeClose">
    <SoundBar v-if="loading"/>
    <form class="user-add" @submit.prevent="submit" v-else data-testid="add-user-form">
      <header>
        <h1>Add New User</h1>
      </header>

      <div>
        <div class="form-row">
          <label>Name</label>
          <input title="Name" type="text" name="name" v-model="newUser.name" required v-koel-focus>
        </div>
        <div class="form-row">
          <label>Email</label>
          <input title="Email" type="email" name="email" v-model="newUser.email" required>
        </div>
        <div class="form-row">
          <label>Password</label>
          <input
            title="Password"
            type="password"
            name="password"
            v-model="newUser.password"
            autocomplete="new-password"
            required
          >
          <p class="help">Min. 10 characters. Must be a mix of characters, numbers, and symbols.</p>
        </div>
        <div class="form-row">
          <label>
            <input type="checkbox" name="is_admin" v-model="newUser.is_admin"> User is an admin
            <TooltipIcon title="Admins can perform administrative tasks like managing users and uploading songs."/>
          </label>
        </div>
      </div>

      <footer>
        <Btn class="btn-add" type="submit">Save</Btn>
        <Btn class="btn-cancel" @click.prevent="maybeClose" white>Cancel</Btn>
      </footer>
    </form>
  </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, reactive, ref } from 'vue'
import { isEqual } from 'lodash'
import { CreateUserData, userStore } from '@/stores'
import { alerts, parseValidationError } from '@/utils'

const Btn = defineAsyncComponent(() => import('@/components/ui/btn.vue'))
const SoundBar = defineAsyncComponent(() => import('@/components/ui/sound-bar.vue'))
const TooltipIcon = defineAsyncComponent(() => import('@/components/ui/tooltip-icon.vue'))

const loading = ref(false)
const emptyUserData = reactive<CreateUserData>({
  name: '',
  email: '',
  password: '',
  is_admin: false
})

const newUser = reactive<CreateUserData>({} as unknown as CreateUserData)

Object.assign(newUser, emptyUserData)

const submit = async () => {
  loading.value = true

  try {
    await userStore.store(newUser)
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
