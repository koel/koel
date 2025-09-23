<template>
  <form novalidate @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Invite Users</h1>
    </header>

    <main class="space-y-5">
      <FormRow>
        <template #label>Emails</template>
        <TextArea
          ref="emailsEl"
          v-model="data.raw_emails"
          v-koel-focus
          class="!min-h-[8rem]"
          name="emails"
          required
          title="Emails"
        />
        <template #help>To invite multiple users, input one email per line.</template>
      </FormRow>
      <RolePicker v-model="data.role" />
    </main>

    <footer>
      <Btn class="btn-add" type="submit">Invite</Btn>
      <Btn class="btn-cancel" white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { ref, watch } from 'vue'
import { invitationService } from '@/services/invitationService'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import TextArea from '@/components/ui/form/TextArea.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import RolePicker from '@/components/user/RolePicker.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

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
      emailsEl.value!.el?.setCustomValidity('One or some of the emails you entered are invalid.')
      emailsEl.value!.el?.reportValidity()
      return false
    }

    if (validEmails.length === 0) {
      emailsEl.value!.el?.setCustomValidity('Please enter at least one email address.')
      emailsEl.value!.el?.reportValidity()
      return false
    }

    return true
  },
  onSubmit: async ({ role }) => invitationService.invite(collectValidEmails(), role),
  onSuccess: () => {
    toastSuccess('Invitation(s) sent.')
    close()
  },
})

watch(() => data.raw_emails, val => {
  emailEntries = val.trim().split('\n').map(email => email.trim()).filter(Boolean)
  emailEntries = [...new Set(emailEntries)]
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog('Discard all changes?')) {
    close()
  }
}
</script>
