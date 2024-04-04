<template>
  <article class="text-secondary">
    Instead of using a password, you can scan the QR code below to log in to
    <a href="https://koel.dev/#mobile" target="_blank" class="text-highlight">Koel Player</a>
    on your mobile device.<br>
    The QR code will refresh every 10 minutes.
    <img :src="qrCodeUrl" alt="QR Code" width="192" height="192">
  </article>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useQRCode } from '@vueuse/integrations/useQRCode'
import { authService } from '@/services'
import { base64Encode } from '@/utils'

const qrCodeData = ref('')
const oneTimeToken = ref('')

watch(oneTimeToken, () => {
  qrCodeData.value = base64Encode(JSON.stringify({
    token: oneTimeToken.value,
    url: window.BASE_URL
  }))
})

const qrCodeUrl = useQRCode(qrCodeData, {
  width: window.devicePixelRatio === 1 ? 196 : 384,
  height: window.devicePixelRatio === 1 ? 196 : 384
})

const resetOneTimeToken = async () => (oneTimeToken.value = await authService.getOneTimeToken())

onMounted(() => {
  window.setInterval(resetOneTimeToken, 60 * 10 * 1000)
  resetOneTimeToken()
})
</script>

<style scoped lang="postcss">
img {
  margin-top: 1.5rem;
  display: block;
  border-radius: 8px;
}
</style>
