<template>
  <div class="overlay" v-if="copiedUser">
    <sound-bar v-if="loading"/>
    <form class="user-edit" @submit.prevent="submit" v-else>
      <header>
        <h1>编辑用户</h1>
      </header>

      <div>
        <div class="form-row">
          <label>姓名</label>
          <input type="text" name="name" v-model="copiedUser.name" required v-koel-focus>
        </div>
        <div class="form-row">
          <label>电子邮件</label>
          <input type="email" name="email" v-model="copiedUser.email" required>
        </div>
        <div class="form-row">
          <label>密码</label>
          <input type="password" name="password" v-model="copiedUser.password" placeholder="Leave blank for no changes">
        </div>
      </div>

      <footer>
        <button class="btn btn-green btn-update">更新</button>
        <button class="btn btn-white btn-cancel" @click.prevent="cancel">取消</button>
      </footer>
    </form>
  </overlay>
</template>

<script>
import { clone } from 'lodash'
import soundBar from '../shared/sound-bar.vue'
import { userStore } from '../../stores'

export default {
  name: 'modals--edit-user-form',
  components: { soundBar },

  data () {
    return {
      user: null,
      loading: false,
      // We work on a cloned version of the user
      copiedUser: null
    }
  },

  methods: {
    open (user) {
      this.copiedUser = clone(user)
      // Keep a reference
      this.user = user
    },

    submit () {
      this.loading = true
      userStore.update(this.user, this.copiedUser.name, this.copiedUser.email, this.copiedUser.password)
        .then(() => {
          this.loading = false
          this.copiedUser = null
        }
      )
    },

    cancel () {
      this.copiedUser = null
    }
  }
}
</script>
