<template>
  <section id="profileWrapper">
    <h1 class="heading">
      <span>个人资料 &amp; 首选项</span>
    </h1>

    <div class="main-scroll-wrap">
      <form @submit.prevent="update">
        <div class="form-row">
          <label for="inputProfileName">姓名</label>
          <input type="text" name="name" id="inputProfileName" v-model="state.current.name">
        </div>

        <div class="form-row">
          <label for="inputProfileEmail">邮箱地址</label>
          <input type="email" name="email" id="inputProfileEmail" v-model="state.current.email">
        </div>

        <div class="change-pwd">
          <div class="form-row">
            <p class="help">请在下方输入你要修改的密码 —— 当然没人会强迫你改 <br>
              不想改?留空即可.</p>
          </div>

          <div class="form-row">
            <label for="inputProfilePassword">新密码</label>
            <input v-model="pwd" name="password" type="password" id="inputProfilePassword" autocomplete="off">
          </div>

          <div class="form-row">
            <label for="inputProfileConfirmPassword">确认您的新密码</label>
            <input v-model="confirmPwd" name="confirmPassword" type="password" id="inputProfileConfirmPassword" autocomplete="off">
          </div>
        </div>

        <div class="form-row">
          <button type="submit" class="btn btn-submit">保存</button>
        </div>
      </form>

      <div class="preferences">
        <div class="form-row">
          <label>
            <input type="checkbox" name="notify" v-model="prefs.notify" @change="savePreference">
            显示"正在播放歌曲"通知
          </label>
        </div>
        <div class="form-row">
          <label>
            <input type="checkbox" name="confirmClosing" v-model="prefs.confirmClosing" @change="savePreference">
            关闭Koel前确认
          </label>
        </div>
      </div>

      <section class="lastfm" >
        <h1>Last.fm模块</h1>

        <div v-if="sharedState.useLastfm">
          <p>此版本的Koel已经与Last.fm整合.
            <span v-if="state.current.preferences.lastfm_session_key">
		我们现在已经成功连接到Last.fm,恭喜!.
            </span>
            <span v-else>
		很抱歉，连接Last.fm失败，请检查配置并重试.
            </span>
          </p>
          <p>
		通过连接Koel和Last.fm，您可以使用各种激动人心的功能，比如记录歌曲
          </p>
          <p v-if="state.current.preferences.lastfm_session_key">
		您随时可以断开和Last.fm的连接，此操作需要重启Koel.
          </p>

          <div class="buttons">
            <button @click.prevent="connectToLastfm" class="connect">
              <i class="fa fa-lastfm"></i>
              {{ state.current.preferences.lastfm_session_key ? '重新连接' : '连接' }}
            </button>

            <button
              v-if="state.current.preferences.lastfm_session_key"
              @click.prevent="disconnectFromLastfm"
              class="disconnect"
            >
              断开
            </button>
          </div>
        </div>

        <div v-else>
          <p>貌似没有安装 Last.fm 模块，请检查.
            <span v-if="state.current.is_admin">请访问
              <a href="https://github.com/phanan/koel/wiki" target="_blank">Koel’s Wiki</a>
              来修复这个问题.
            </span>
            <span v-else>联系你的管理员开启Last.fm.</span>
          </p>
        </div>
      </section>
    </div>
  </section>
</template>

<script>
import $ from 'jquery'

import { userStore, preferenceStore, sharedStore } from '../../../stores'
import { forceReloadWindow } from '../../../utils'
import { http, ls } from '../../../services'

export default {
  data () {
    return {
      state: userStore.state,
      cache: userStore.stub,
      pwd: '',
      confirmPwd: '',
      prefs: preferenceStore.state,
      sharedState: sharedStore.state
    }
  },

  methods: {
    /**
     * Update the current user's profile.
     */
    update () {
      // A little validation put in a small place.
      if ((this.pwd || this.confirmPwd) && this.pwd !== this.confirmPwd) {
        $('#inputProfilePassword, #inputProfileConfirmPassword').addClass('error')
        return
      }

      $('#inputProfilePassword, #inputProfileConfirmPassword').removeClass('error')

      userStore.updateProfile(this.pwd).then(() => {
        this.pwd = ''
        this.confirmPwd = ''
      })
    },

    /**
     * Save the current user's preference.
     */
    savePreference () {
      this.$nextTick(() => preferenceStore.save())
    },

    /**
     * Connect the current user to Last.fm.
     * This method opens a new window.
     * Koel will reload once the connection is successful.
     */
    connectToLastfm () {
      window.open(
        `/api/lastfm/connect?jwt-token=${ls.get('jwt-token')}`,
        '_blank',
        'toolbar=no,titlebar=no,location=no,width=1024,height=640'
      )
    },

    /**
     * Disconnect the current user from Last.fm.
     * Oh God why.
     */
    disconnectFromLastfm () {
      // Should we use userStore?
      // - We shouldn't. This doesn't have anything to do with stores.
      // Should we confirm the user?
      // - Nope. Users should be grown-ass adults who take responsibilty of their actions.
      // But one of my users is my new born kid!
      // - Then? Kids will fuck things up anyway.
      http.delete('lastfm/disconnect', {}, forceReloadWindow)
    }
  }
}
</script>

<style lang="sass">
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";

#profileWrapper {
  input {
    &[type="text"], &[type="email"], &[type="password"] {
      width: 192px;
    }

    &.error {
      // Chrome won't give up its autofill style, so this is kind of a hack.
      box-shadow: 0 0 0px 1000px #ff867a inset;
    }
  }

  .change-pwd {
    margin-top: 24px;
  }

  .status {
    margin-left: 8px;
    color: $colorGreen;
  }

  .preferences {
    margin-top: 32px;
    border-top: 1px solid $color2ndBgr;

    label {
      font-size: $fontSize;
    }
  }

  .lastfm {
    border-top: 1px solid $color2ndBgr;
    color: $color2ndText;
    margin-top: 16px;
    padding-top: 16px;

    a {
      color: $colorHighlight;
    }

    h1 {
      font-size: 24px;
      margin-bottom: 16px;
    }

    .buttons {
      margin-top: 16px;

      .connect {
        background: #d31f27; // Last.fm color yo!
      }

      .disconnect {
        background: $colorGrey; // Our color yo!
      }
    }
  }

  @media only screen and (max-width : 667px) {
    input {
      &[type="text"], &[type="email"], &[type="password"] {
        width: 100%;
        height: 32px;
      }
    }
  }
}
</style>
