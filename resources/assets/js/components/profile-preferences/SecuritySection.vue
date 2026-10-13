<template>
  <div class="flex flex-col gap-6">
    <section
      v-for="(subsection, index) in subsections"
      :key="index"
      class="rounded-xl border border-k-fg-10 bg-k-fg-3 p-6"
    >
      <component :is="subsection" />
    </section>
  </div>
</template>

<script lang="ts" setup>
import ChangePasswordForm from '@/components/profile-preferences/ChangePasswordForm.vue'
import TwoFactorAuthSettings from '@/components/auth/two-factor/TwoFactorAuthSettings.vue'
import PasskeySettings from '@/components/auth/passkey/PasskeySettings.vue'
import OtherSessions from '@/components/auth/OtherSessions.vue'

const subsections = [ChangePasswordForm, TwoFactorAuthSettings, PasskeySettings, OtherSessions]
</script>
