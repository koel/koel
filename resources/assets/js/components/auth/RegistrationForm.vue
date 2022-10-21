<template>
  <form :class="{ error: failed }" data-testid="login-form" @submit.prevent="register">

    <input v-model="newUser.name" autofocus placeholder="User Name" required >
    <input v-model="newUser.email" autofocus placeholder="Email Address" required type="email">
    <input v-model="newUser.password" placeholder="Password" required type="password">
    <input v-model="newUser.password_confirmation" placeholder="Confirm Password" required type="password">
    <Btn type="submit">Register</Btn>
  </form>
</template>

<script lang="ts" setup>
import { reactive, ref } from 'vue'
import { RegisterUserData, CreateUserData, userStore } from '@/stores'
import { parseValidationError, requireInjection } from '@/utils'
import Btn from '@/components/ui/Btn.vue'



const url = ref('')
const email = ''
const password = ''
const failed = ref(false)
const username= ''
const password_confirmation = ''
const emit = defineEmits(['loggedin'])






const emptyUserData: RegisterUserData = {
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  is_admin: false
}

const newUser = reactive<RegisterUserData>(Object.assign({}, emptyUserData))

// const register = async () => {
//   try {
//     await userStore.store(email.value, password.value)
//     failed.value = false

//     // Reset the password so that the next login will have this field empty.
//     password.value = ''

//     emit('loggedin')
//   } catch (err) {
//     failed.value = true
//     window.setTimeout(() => (failed.value = false), 2000)
//   }
// }


const register = async () => {


  try {
    await userStore.register(newUser)
    failed.value =false
    emit('loggedin')

  } catch (err: any) {
    failed.value = true
    const msg = err.response.status === 422 ? parseValidationError(err.response.data)[0] : 'Unknown error.'
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
    transform: translateX(-10px);
  }
  25%, 58% {
    transform: translateX(10px);
  }
  75% {
    transform: translateX(-5px);
  }
  92% {
    transform: translateX(5px);
  }
  0%, 100% {
    transform: translateX(0);
  }
}

form {
  width: 280px;
  padding: 1.8rem;
  background: rgba(255, 255, 255, .08);
  border-radius: .6rem;
  border: 1px solid transparent;
  transition: .5s;
  display: flex;
  flex-direction: column;
  gap: 1rem;

  &.error {
    border-color: var(--color-red);
    animation: shake .5s;
  }

  @media only screen and (max-width: 414px) {
    border: 0;
    background: transparent;
  }
}
</style>
