/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_KOEL_ENV: 'demo' | undefined
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
