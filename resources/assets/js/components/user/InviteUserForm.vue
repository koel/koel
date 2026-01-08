<template>
  <form novalidate @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>{{ t('users.invite') }}</h1>
    </header>

    <main class="space-y-5">
      <FormRow>
        <template #label>{{ t('users.emails') }}</template>
        <TextArea
          ref="emailsEl"
          v-model="data.raw_emails"
          v-koel-focus
          class="!min-h-[8rem]"
          name="emails"
          required
          :title="t('users.emails')"
        />
        <template #help>{{ t('users.inviteInstruction') }}</template>
      </FormRow>
      <RolePicker v-model="data.role" />
    </main>

    <footer>
      <Btn class="btn-add" type="submit">{{ t('users.inviteButton') }}</Btn>
      <Btn class="btn-cancel" white @click.prevent="maybeClose">{{ t('auth.cancel') }}</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { invitationService } from '@/services/invitationService'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import TextArea from '@/components/ui/form/TextArea.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import RolePicker from '@/components/user/RolePicker.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const { t } = useI18n()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const emailsEl = ref<InstanceType<typeof TextArea>>()

let emailEntries: string[] = []

const collectValidEmails = () => {
  const validEmails: string[] = []
  const input = document.createElement('input')
  input.type = 'email'

  emailEntries.forEach(email => {
    input.value = email
    input.checkValidity() && validEmails.push(email)
  })

  return validEmails
}

const close = () => emit('close')

const { data, isPristine, handleSubmit } = useForm<{ raw_emails: string, role: Role }>({
  initialValues: {
    raw_emails: '',
    role: 'user',
  },
  validator: () => {
    const validEmails = collectValidEmails()

    if (validEmails.length !== emailEntries.length) {
      emailsEl.value!.el?.setCustomValidity(t('users.invalidEmails'))
      emailsEl.value!.el?.reportValidity()
      return false
    }

    if (validEmails.length === 0) {
      emailsEl.value!.el?.setCustomValidity(t('users.noEmails'))
      emailsEl.value!.el?.reportValidity()
      return false
    }

    return true
  },
  onSubmit: async ({ role }) => invitationService.invite(collectValidEmails(), role),
  onSuccess: () => {
    toastSuccess(t('users.invitationSent'))
    close()
  },
})

watch(() => data.raw_emails, val => {
  emailEntries = val.trim().split('\n').map(email => email.trim()).filter(Boolean)
  emailEntries = [...new Set(emailEntries)]
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog(t('playlists.discardChanges'))) {
    close()
  }
}
</script>
