<template>
  <form @submit.prevent="login" :class="{ error: failed }" data-testid="login-form">
    <div class="logo">
      <img src="@/../img/logo.svg" width="156" height="auto" alt="Koel's logo">
    </div>
    <input v-if="isDesktopApp" v-model="url" type="text" placeholder="Koel's Host" autofocus required>
    <input v-model="email" type="email" placeholder="Email Address" autofocus required>
    <input v-model="password" type="password" placeholder="Password" required>
    <btn type="submit">Log In</btn>
  </form>
</template>

<script lang="ts">
import Vue from 'vue'
import axios from 'axios'
import { userStore } from '@/stores'
import { ls } from '@/services'

const DEMO_ACCOUNT = {
  email: 'demo@koel.dev',
  password: 'demo'
}

export default Vue.extend({
  components: {
    Btn: () => import('@/components/ui/btn.vue')
  },

  data: () => ({
    url: '',
    email: NODE_ENV === 'demo' ? DEMO_ACCOUNT.email : '',
    password: NODE_ENV === 'demo' ? DEMO_ACCOUNT.password : '',
    failed: false,
    isDesktopApp: KOEL_ENV === 'app'
  }),

  methods: {
    async login (): Promise<void> {
      if (KOEL_ENV === 'app') {
        if (this.url.indexOf('http://') !== 0 && this.url.indexOf('https://') !== 0) {
          this.url = `https://${this.url}`
        }

        if (!this.url.endsWith('/')) {
          this.url = `${this.url}/`
        }

        axios.defaults.baseURL = `${this.url}api`
      }

      try {
        await userStore.login(this.email, this.password)
        this.failed = false

        // Reset the password so that the next login will have this field empty.
        this.password = ''

        if (KOEL_ENV === 'app') {
          ls.set('koelHost', this.url)
          ls.set('lastLoginEmail', this.email)
        }

        this.$emit('loggedin')
      } catch (err) {
        this.failed = true
        window.setTimeout((): void => {
          this.failed = false
        }, 2000)
      }
    }
  },

  mounted (): void {
    if (KOEL_ENV === 'app') {
      this.url = window.BASE_URL = String(ls.get<string>('koelHost'))
      this.email = String(ls.get('lastLoginEmail'))
    }
  }
})
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

  @media only screen and (max-width : 414px) {
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
