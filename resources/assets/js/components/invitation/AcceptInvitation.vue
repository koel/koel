<template>
  <div class="invitation-wrapper">
    <form v-if="userProspect" autocomplete="off" @submit.prevent="submit">
      <header>
        Welcome to Koel! To accept the invitation, fill in the form below and click that button.
      </header>

      <div class="form-row">
        <label>
          Your email
          <input type="text" :value="userProspect.email" disabled>
        </label>
      </div>

      <div class="form-row">
        <label>
          Your name
          <input v-model="formData.name" v-koel-focus type="text" required placeholder="Erm… Bruce Dickinson?">
        </label>
      </div>

      <div class="form-row">
        <label>
          Password
          <PasswordField v-model="formData.password" minlength="10" />
          <small>Min. 10 characters. Should be a mix of characters, numbers, and symbols.</small>
        </label>
      </div>

      <div class="form-row">
        <Btn type="submit">Accept &amp; Log In</Btn>
      </div>
    </form>

    <p v-if="!validToken">Invalid or expired invite.</p>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { invitationService } from '@/services'
import { useDialogBox, useRouter } from '@/composables'

import Btn from '@/components/ui/Btn.vue'
import PasswordField from '@/components/ui/PasswordField.vue'

import { parseValidationError } from '@/utils'

const { showErrorDialog } = useDialogBox()
const { getRouteParam, go } = useRouter()

const userProspect = ref<User>()
const validToken = ref(true)

const formData = reactive<{ name: string, password: string }>({
  name: '',
  password: ''
})

const token = String(getRouteParam('token')!)

const submit = async () => {
  try {
    await invitationService.accept(token, formData.name, formData.password)
    window.location.href = '/'
  } catch (err: any) {
    const msg = err.response.status === 422 ? parseValidationError(err.response.data)[0] : 'Unknown error.'
    showErrorDialog(msg, 'Error')
  }
}

onMounted(async () => {
  try {
    userProspect.value = await invitationService.getUserProspect(token)
  } catch (err: any) {
    if (err.response.status === 404) {
      validToken.value = false
      return
    }

    const msg = err.response.status === 422 ? parseValidationError(err.response.data)[0] : 'Unknown error.'
    showErrorDialog(msg, 'Error')
  }
})
</script>

<style scoped lang="scss">
.invitation-wrapper {
  @include vertical-center();

  display: flex;
  height: 100vh;
  flex-direction: column;
  justify-content: center;
}

header {
  margin-bottom: 1.2rem;
}

small {
  margin-top: .8rem;
  font-size: .9rem;
  display: block;
  line-height: 1.4;
  color: var(--color-text-secondary);
}

form {
  width: 320px;
  padding: 1.8rem;
  background: rgba(255, 255, 255, .08);
  border-radius: .6rem;
  display: flex;
  flex-direction: column;

  input {
    width: 100%;
  }

  @media only screen and (max-width: 414px) {
    border: 0;
    background: transparent;
  }
}
</style>
