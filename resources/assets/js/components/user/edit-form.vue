<template>
  <div class="edit-user" @keydown.esc="maybeClose">
    <SoundBar v-if="loading"/>
    <form class="user-edit" @submit.prevent="submit" v-else data-testid="edit-user-form">
      <header>
        <h1>Edit User</h1>
      </header>

      <div>
        <div class="form-row">
          <label>Name</label>
          <input title="Name" type="text" name="name" v-model="updateData.name" required v-koel-focus>
        </div>
        <div class="form-row">
          <label>Email</label>
          <input title="Email" type="email" name="email" v-model="updateData.email" required>
        </div>
        <div class="form-row">
          <label>Password</label>
          <input
            name="password"
            placeholder="Leave blank for no changes"
            type="password"
            v-model="updateData.password"
            autocomplete="new-password"
          >
          <p class="help">Min. 10 characters. Must be a mix of characters, numbers, and symbols.</p>
        </div>
        <div class="form-row">
          <label>
            <input type="checkbox" name="is_admin" v-model="updateData.is_admin"> User is an admin
            <TooltipIcon title="Admins can perform administrative tasks like managing users and uploading songs."/>
          </label>
        </div>
      </div>

      <footer>
        <Btn class="btn-update" type="submit">Update</Btn>
        <Btn class="btn-cancel" @click.prevent="maybeClose" white data-test="cancel-btn">Cancel</Btn>
      </footer>
    </form>
  </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, onMounted, reactive, ref, toRefs } from 'vue'
import { isEqual } from 'lodash'
import { alerts, parseValidationError } from '@/utils'
import { UpdateUserData, userStore } from '@/stores'

const Btn = defineAsyncComponent(() => import('@/components/ui/btn.vue'))
const SoundBar = defineAsyncComponent(() => import('@/components/ui/sound-bar.vue'))
const TooltipIcon = defineAsyncComponent(() => import('@/components/ui/tooltip-icon.vue'))

const props = defineProps<{ user: User }>()
const { user } = toRefs(props)

const loading = ref(false)
const updateData = reactive({} as unknown as UpdateUserData)
const originalData = reactive({} as unknown as UpdateUserData)

const submit = async () => {
  loading.value = true

  try {
    await userStore.update(user.value, updateData)
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
  if (isEqual(originalData, updateData)) {
    close()
    return
  }

  alerts.confirm('Discard all changes?', close)
}

onMounted(() => {
  Object.assign(updateData, {
    name: user.value.name,
    email: user.value.email,
    is_admin: user.value.is_admin
  })

  Object.assign(originalData, updateData)
})
</script>

<style lang="scss" scoped>
.help {
  margin-top: .75rem;
}
</style>
