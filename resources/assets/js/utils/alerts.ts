import alertify from 'alertify.js'

type LogType = 'success' | 'error' | 'log'

const encodeEntities = (str: string) => str.replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')

export const alerts = {
  alert: (message: string) => alertify.alert(encodeEntities(message)),
  confirm: (message: string, onOk: Closure, onCancel?: Closure) => alertify.confirm(message, onOk, onCancel),

  log: (message: string, type: LogType, callback?: Closure) => {
    alertify.logPosition('top right')
    alertify.closeLogOnClick(true)
    alertify[type](encodeEntities(message), callback)
  },

  success (message: string, callback?: Closure) {
    this.log(message, 'success', callback)
  },

  error (message: string, callback?: Closure) {
    this.log(message, 'error', callback)
  }
}
