const DEFAULT_EXPIRATION_TIME = 1000 * 60 * 60 * 24 // 1 day

export class Cache {
  private storage = new Map<any, {
    expires: number,
    value: any
  }>()

  private static normalizeKey (key: any) {
    return typeof key === 'object' ? JSON.stringify(key) : key
  }

  public has (key: any) {
    return this.hit(Cache.normalizeKey(key))
  }

  public get<T> (key: any) {
    return this.storage.get(Cache.normalizeKey(key))?.value as T
  }

  public set (key: any, value: any, seconds: number = DEFAULT_EXPIRATION_TIME) {
    this.storage.set(Cache.normalizeKey(key), {
      value,
      expires: Date.now() + seconds * 1000
    })
  }

  public hit (key: any) {
    return !this.miss(Cache.normalizeKey(key))
  }

  public miss (key: any) {
    key = Cache.normalizeKey(key)

    if (!this.storage.has(key)) return true
    const { expires } = this.storage.get(key)!

    if (expires < Date.now()) {
      this.storage.delete(key)
      return true
    }

    return false
  }

  public remove (key: any) {
    this.storage.delete(Cache.normalizeKey(key))
  }

  async remember<T> (key: any, resolver: Closure, seconds: number = DEFAULT_EXPIRATION_TIME) {
    key = Cache.normalizeKey(key)

    this.hit(key) || this.set(key, await resolver(), seconds)
    return this.get<T>(key)
  }
}

export const cache = new Cache()
