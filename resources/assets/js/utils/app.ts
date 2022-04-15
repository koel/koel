// App (electron)-only methods
let mainWindow: any

if (KOEL_ENV === 'app') {
  mainWindow = require('electron').remote.getCurrentWindow()
}

export const app = {
  triggerMaximize: (): void =>
    mainWindow && (mainWindow.isMaximized() ? mainWindow.unmaximize() : mainWindow.maximize())
}
