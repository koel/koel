<template>
  <form @submit.prevent="login" :class="{ error: failed }" data-testid="login-form">
    <div class="logo">
      <img src="@/../img/logo.svg" width="156" height="auto" alt="Koel's logo">
    </div>
    <input v-model="email" type="email" placeholder="Email Address" autofocus required>
    <input v-model="password" type="password" placeholder="Password" required>
    <btn type="submit">Log In</btn>
  </form>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, ref } from 'vue'
import { userStore } from '@/stores'

const DEMO_ACCOUNT = {
  email: 'demo@koel.dev',
  password: 'demo'
}

const Btn = defineAsyncComponent(() => import('@/components/ui/btn.vue'))

const url = ref('')
const email = ref(NODE_ENV === 'demo' ? DEMO_ACCOUNT.email : '')
const password = ref(NODE_ENV === 'demo' ? DEMO_ACCOUNT.password : '')
const failed = ref(false)

const emit = defineEmits(['loggedin'])

const login = async () => {
  try {
    await userStore.login(email.value, password.value)
    failed.value = false

    // Reset the password so that the next login will have this field empty.
    password.value = ''

    emit('loggedin')
  } catch (err) {
    failed.value = true
    window.setTimeout(() => (failed.value = false), 2000)
  }
}
</script>

<style lang="scss" scoped>
/**
 * I like to move it move it
 * I like to move it move it
 * I like to move it move it
 * You like to - move it!
 */
@keyframes shake {
  8%, 41% {
    -webkit-transform: translateX(-10px);
  }
  25%, 58% {
    -webkit-transform: translateX(10px);
  }
  75% {
    -webkit-transform: translateX(-5px);
  }
  92% {
    -webkit-transform: translateX(5px);
  }
  0%, 100% {
    -webkit-transform: translateX(0);
  }
}

form {
  width: 280px;
  padding: 1.8rem;
  background: rgba(255, 255, 255, .08);
  border-radius: .6rem;
  border: 1px solid transparent;
  transition: .5s;

  > * + * {
    margin-top: 1rem;
  }

  &.error {
    border-color: var(--color-red);
    animation: shake .5s;
  }

  .logo {
    text-align: center;
  }

  @media only screen and (max-width: 414px) {
    border: 0;
    background: transparent;
  }
}

input {
  display: block;
  border: 0;
  outline: none;
  width: 100%;
}

button {
  display: block;
  margin-top: 12px;
  width: 100%;
}
</style>
