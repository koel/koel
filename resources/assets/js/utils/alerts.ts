import alertify from 'alertify.js'

type logType = 'success' | 'error' | 'log'

const encodeEntities = (str: string) => str.replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')

export const alerts = {
  alert: (msg: string): void => alertify.alert(encodeEntities(msg)),

  confirm: (msg: string, okFunc: Function, cancelFunc?: Function): void => {
    alertify.confirm(msg, okFunc, cancelFunc)
  },

  log: (msg: string, type: logType = 'log', cb?: Function): void => {
    alertify.logPosition('top right')
    alertify.closeLogOnClick(true)
    alertify[type](encodeEntities(msg), cb)
  },

  success (msg: string, cb?: Function): void {
    this.log(msg, 'success', cb)
  },

  error (msg: string, cb?: Function): void {
    this.log(msg, 'error', cb)
  }
}
