import alertify from 'alertify.js'

type LogType = 'success' | 'error' | 'log'

const encodeEntities = (str: string) => str.replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')

export const alerts = {
  alert: (msg: string) => alertify.alert(encodeEntities(msg)),

  confirm: (msg: string, okFunc: Closure, cancelFunc?: Closure) => {
    alertify.confirm(msg, okFunc, cancelFunc)
  },

  log: (msg: string, type: LogType = 'log', cb?: Closure) => {
    alertify.logPosition('top right')
    alertify.closeLogOnClick(true)
    alertify[type](encodeEntities(msg), cb)
  },

  success (msg: string, cb?: Closure) {
    this.log(msg, 'success', cb)
  },

  error (msg: string, cb?: Closure) {
    this.log(msg, 'error', cb)
  }
}
