<template>
  <div class="flex items-center justify-center h-screen flex-col">
    <form
      v-if="userProspect"
      autocomplete="off"
      class="w-full sm:w-[320px] p-7 sm:bg-white/10 rounded-lg flex flex-col space-y-5"
      @submit.prevent="submit"
    >
      <header class="mb-4">
        Welcome to Koel! To accept the invitation, fill in the form below and click that button.
      </header>

      <FormRow>
        <template #label>Your email</template>
        <TextInput v-model="userProspect.email" disabled />
      </FormRow>

      <FormRow>
        <template #label>Your name</template>
        <TextInput
          v-model="name"
          v-koel-focus
          data-testid="name"
          placeholder="Erm… Bruce Dickinson?"
          required
        />
      </FormRow>

      <FormRow>
        <template #label>Password</template>
        <PasswordField v-model="password" data-testid="password" required />
        <template #help>Min. 10 characters. Should be a mix of characters, numbers, and symbols.</template>
      </FormRow>

      <FormRow>
        <Btn :disabled="loading" data-testid="submit" type="submit">Accept &amp; Log In</Btn>
      </FormRow>
    </form>

    <p v-if="!validToken">Invalid or expired invite.</p>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { invitationService } from '@/services'
import { useErrorHandler, useRouter } from '@/composables'

import Btn from '@/components/ui/form/Btn.vue'
import PasswordField from '@/components/ui/form/PasswordField.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const { getRouteParam } = useRouter()
const { handleHttpError } = useErrorHandler('dialog')

const name = ref('')
const password = ref('')
const userProspect = ref<User>()
const validToken = ref(true)
const loading = ref(false)

const token = String(getRouteParam('token')!)

const submit = async () => {
  try {
    loading.value = true
    await invitationService.accept(token, name.value, password.value)
    window.location.href = '/'
  } catch (error: unknown) {
    handleHttpError(error)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try {
    userProspect.value = await invitationService.getUserProspect(token)
  } catch (error: unknown) {
    handleHttpError(error, { 404: () => (validToken.value = false) })
  }
})
</script>
