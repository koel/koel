
declare module 'vue-test-helpers' {
  export default function (options?: { registerGlobals: boolean }): void
}

declare module 'crypto-random-string' {
  export default function (length: number): string
}

declare namespace NodeJS {
  interface Global {
    Vue: any
    __UNIT_TESTING__: boolean
    _: any
    noop: Function
    IntersectionObserver: any

    document: Document
    window: Window
    navigator: Navigator
  }
}
