<template>
  <div class="flex flex-col gap-12">
    <ChangePasswordForm />
    <TwoFactorAuthSettings />
    <PasskeySettings />
  </div>
</template>

<script lang="ts" setup>
import ChangePasswordForm from '@/components/profile-preferences/ChangePasswordForm.vue'
import TwoFactorAuthSettings from '@/components/auth/two-factor/TwoFactorAuthSettings.vue'
import PasskeySettings from '@/components/auth/passkey/PasskeySettings.vue'
</script>
