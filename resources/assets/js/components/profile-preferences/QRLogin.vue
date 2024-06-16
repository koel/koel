<template>
  <article class="text-k-text-secondary">
    Instead of using a password, you can scan the QR code below to log in to
    <a href="https://koel.dev/#mobile" target="_blank">Koel Player</a>
    on your mobile device.<br>
    The QR code will refresh every 10 minutes.
    <a role="button" @click.prevent="resetOneTimeToken">Refresh now</a>
    <img :src="qrCodeUrl" alt="QR Code" class="mt-4 rounded-4" height="192" width="192">
  </article>
</template>

<script lang="ts" setup>
import { onMounted, ref, watch } from 'vue'
import { useQRCode } from '@vueuse/integrations/useQRCode'
import { authService } from '@/services'
import { base64Encode } from '@/utils'

const qrCodeData = ref('')
const oneTimeToken = ref('')

watch(oneTimeToken, () => {
  qrCodeData.value = base64Encode(JSON.stringify({
    token: oneTimeToken.value,
    host: window.BASE_URL
  }))
})

const qrCodeUrl = useQRCode(qrCodeData, {
  width: window.devicePixelRatio === 1 ? 196 : 384,
  height: window.devicePixelRatio === 1 ? 196 : 384
})

let oneTimeTokenTimeout: number | null = null

const resetOneTimeToken = async () => {
  oneTimeToken.value = await authService.getOneTimeToken()

  if (oneTimeTokenTimeout) {
    window.clearTimeout(oneTimeTokenTimeout)
  }

  oneTimeTokenTimeout = window.setTimeout(resetOneTimeToken, 60 * 10 * 1000)
}

onMounted(() => resetOneTimeToken())
</script>
