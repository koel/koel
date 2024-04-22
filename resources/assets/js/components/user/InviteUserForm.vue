<template>
  <form novalidate @submit.prevent="submit" @keydown.esc="maybeClose">
    <header>
      <h1>Invite Users</h1>
    </header>

    <main class="space-y-5">
      <FormRow>
        <template #label>Emails</template>
        <TextArea ref="emailsEl" v-model="rawEmails" name="emails" required title="Emails" />
        <template #help>To invite multiple users, input one email per line.</template>
      </FormRow>
      <FormRow>
        <div class="text-base">
          <CheckBox v-model="isAdmin" name="is_admin" />
          Admin role
          <TooltipIcon title="Admins can perform administrative tasks like managing users and uploading songs." />
        </div>
      </FormRow>
    </main>

    <footer>
      <Btn class="btn-add" type="submit">Invite</Btn>
      <Btn class="btn-cancel" white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { ref, watch } from 'vue'
import { parseValidationError } from '@/utils'
import { useDialogBox, useMessageToaster, useOverlay } from '@/composables'
import { invitationService } from '@/services'

import Btn from '@/components/ui/form/Btn.vue'
import TooltipIcon from '@/components/ui/TooltipIcon.vue'
import CheckBox from '@/components/ui/form/CheckBox.vue'
import TextArea from '@/components/ui/form/TextArea.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showErrorDialog, showConfirmDialog } = useDialogBox()

const emailsEl = ref<InstanceType<typeof TextArea>>()
const rawEmails = ref('')
const isAdmin = ref(false)

let emailEntries: string[] = []

watch(rawEmails, val => {
  emailEntries = val.trim().split('\n').map(email => email.trim()).filter(Boolean)
  emailEntries = [...new Set(emailEntries)]
})

const submit = async () => {
  const validEmails: string[] = []
  const validator = document.createElement('input')
  validator.type = 'email'

  emailEntries.forEach(email => {
    validator.value = email
    validator.checkValidity() && validEmails.push(email)
  })

  if (validEmails.length !== emailEntries.length) {
    emailsEl.value!.el?.setCustomValidity('One or some of the emails you entered are invalid.')
    emailsEl.value!.el?.reportValidity()
    return
  }

  if (validEmails.length === 0) {
    emailsEl.value!.el?.setCustomValidity('Please enter at least one email address.')
    emailsEl.value!.el?.reportValidity()
    return
  }

  showOverlay()

  try {
    await invitationService.invite(validEmails, isAdmin.value)
    toastSuccess(`Invitation${validEmails.length === 1 ? '' : 's'} sent.`)
    close()
  } catch (err: any) {
    const msg = err.response.status === 422 ? parseValidationError(err.response.data)[0] : 'Unknown error.'
    showErrorDialog(msg, 'Error')
  } finally {
    hideOverlay()
  }
}

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const maybeClose = async () => {
  if (emailEntries.length === 0 && !isAdmin.value) {
    close()
    return
  }

  await showConfirmDialog('Discard all changes?') && close()
}
</script>

<style lang="postcss" scoped>
textarea {
  min-height: 8rem !important;
}

small.help {
  margin: .75rem 0 .5rem;
  display: block;
}
</style>
