<template>
  <div class="flex items-center justify-center h-screen flex-col">
    <form
      v-if="userProspect"
      autocomplete="off"
      class="w-full sm:w-[320px] p-7 sm:bg-k-fg-10 rounded-lg flex flex-col space-y-5"
      @submit.prevent="submit"
    >
      <header class="mb-4">
        {{ t('content.invitation.welcomeInvitation') }}
      </header>

      <FormRow>
        <template #label>{{ t('content.invitation.yourEmail') }}</template>
        <TextInput v-model="userProspect.email" disabled />
      </FormRow>

      <FormRow>
        <template #label>{{ t('content.invitation.yourName') }}</template>
        <TextInput
          v-model="name"
          v-koel-focus
          data-testid="name"
          :placeholder="t('form.placeholders.erne')"
          required
        />
      </FormRow>

      <FormRow>
        <template #label>{{ t('content.invitation.password') }}</template>
        <PasswordField v-model="password" data-testid="password" required />
        <template #help>{{ t('content.invitation.passwordMinRequirements') }}</template>
      </FormRow>

      <FormRow>
        <Btn :disabled="loading" data-testid="submit" type="submit">{{ t('content.invitation.acceptLogin') }}</Btn>
      </FormRow>
    </form>
  </div>
</template>

<script lang="ts" setup>
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { invitationService } from '@/services/invitationService'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useRouter } from '@/composables/useRouter'

import Btn from '@/components/ui/form/Btn.vue'
import PasswordField from '@/components/ui/form/PasswordField.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const { t } = useI18n()

const { getRouteParam } = useRouter()
const { handleHttpError } = useErrorHandler('dialog')

const name = ref('')
const password = ref('')
const userProspect = ref<User>()
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
    handleHttpError(error, { 404: 'Invalid or expired invite.' })
  }
})
</script>
