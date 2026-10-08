<template>
  <div class="space-y-6">
    <ChangePasswordForm />
    <TwoFactorAuthSettings />
  </div>
</template>

<script lang="ts" setup>
import ChangePasswordForm from '@/components/profile-preferences/ChangePasswordForm.vue'
import TwoFactorAuthSettings from '@/components/auth/two-factor/TwoFactorAuthSettings.vue'
</script>
