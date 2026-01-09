<template>
  <form data-testid="update-profile-form" @submit.prevent="handleSubmit">
    <AlertBox v-if="currentUser.sso_provider">
      <template v-if="currentUser.sso_provider === 'Reverse Proxy'">
        {{ $t('users.reverseProxyAuth') }}
      </template>
      <template v-else>
        {{ $t('users.ssoAuth', { provider: currentUser.sso_provider }) }}
      </template>
      {{ $t('users.ssoNote') }}
    </AlertBox>

    <div class="flex flex-col gap-3 md:flex-row md:gap-8 w-full md:w-[640px]">
      <div class="flex-1 space-y-5">
        <FormRow v-if="!currentUser.sso_provider">
          <template #label>{{ $t('preferences.currentPassword') }}</template>
          <TextInput
            v-model="data.current_password"
            v-koel-focus
            data-testid="currentPassword"
            name="current_password"
            :placeholder="$t('preferences.currentPasswordRequired')"
            required
            type="password"
          />
        </FormRow>

        <FormRow>
          <template #label>{{ $t('preferences.name') }}</template>
          <TextInput v-model="data.name" data-testid="name" name="name" />
        </FormRow>

        <FormRow>
          <template #label>{{ $t('preferences.emailAddress') }}</template>
          <TextInput
            id="inputProfileEmail"
            v-model="data.email"
            :readonly="currentUser.sso_provider"
            data-testid="email"
            name="email"
            required
            type="email"
          />
        </FormRow>

        <FormRow v-if="!currentUser.sso_provider">
          <template #label>{{ $t('preferences.newPassword') }}</template>
          <PasswordField
            v-model="data.new_password"
            autocomplete="new-password"
            data-testid="newPassword"
            minlength="10"
            :placeholder="$t('preferences.leaveEmpty')"
          />
          <template #help>{{ $t('preferences.passwordRequirements') }}</template>
        </FormRow>
      </div>

      <div>
        <EditableProfileAvatar :profile="data" @changed="onAvatarChanged" />
      </div>
    </div>

    <footer class="mt-8">
      <Btn class="btn-submit" type="submit">{{ $t('auth.save') }}</Btn>
      <span v-if="isDemo" class="text-[.95rem] opacity-70 ml-2">
        {{ $t('preferences.demoNotice') }}
      </span>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { pick } from 'lodash'
import { useI18n } from 'vue-i18n'
import type { UpdateCurrentProfileData } from '@/services/authService'
import { authService } from '@/services/authService'
import { useAuthorization } from '@/composables/useAuthorization'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useForm } from '@/composables/useForm'

const { t } = useI18n()

import Btn from '@/components/ui/form/Btn.vue'
import PasswordField from '@/components/ui/form/PasswordField.vue'
import EditableProfileAvatar from '@/components/profile-preferences/EditableProfileAvatar.vue'
import AlertBox from '@/components/ui/AlertBox.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'

const { toastSuccess } = useMessageToaster()
const { currentUser } = useAuthorization()

const isDemo = window.IS_DEMO

const { data, handleSubmit } = useForm<UpdateCurrentProfileData>({
  initialValues: {
    ...pick(currentUser.value, 'name', 'email', 'avatar'),
    current_password: '',
    new_password: '',
  },
  onSubmit: async data => {
    if (isDemo) {
      return
    }

    const formattedData = { ...data }

    // if the new_password field is empty, remove the field entirely
    // to ensure the field doesn't get sent to the server.
    if (!formattedData.new_password) {
      delete formattedData.new_password
    }

    await authService.updateProfile(formattedData)
  },
  onSuccess: () => {
    data.current_password = ''
    data.new_password = ''
    toastSuccess(t('preferences.profileUpdated'))
  },
})

const onAvatarChanged = (avatar: string) => {
  data.avatar = avatar
}
</script>
