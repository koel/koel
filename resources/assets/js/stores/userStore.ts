import { differenceBy } from 'lodash'
import { httpService } from '@/services'
import { reactive } from 'vue'
import { arrayify } from '@/utils'

export interface UpdateCurrentProfileData {
  current_password: string | null
  name: string
  email: string
  avatar?: string
  new_password?: string
}

interface UserFormData {
  name: string
  email: string
  is_admin: boolean
}

export interface CreateUserData extends UserFormData {
  password: string
}

export interface UpdateUserData extends UserFormData {
  password?: string
}

export const userStore = {
  vault: new Map<number, User>(),

  state: reactive({
    users: [] as User[],
    current: null as unknown as User
  }),

  syncWithVault (users: User | User[]) {
    return arrayify(users).map(user => {
      let local = this.byId(user.id)
      local = reactive(local ? Object.assign(local, user) : user)
      this.vault.set(user.id, local)

      return local
    })
  },

  async fetch () {
    this.state.users = this.syncWithVault(await httpService.get<User[]>('users'))
  },

  init (currentUser: User) {
    this.current = currentUser
    this.state.users = this.syncWithVault(this.current)
  },

  byId (id: number) {
    return this.vault.get(id)
  },

  get current () {
    return this.state.current
  },

  set current (user: User) {
    this.state.current = user
  },

  login: async (email: string, password: string) => await httpService.post<User>('me', { email, password }),
  logout: async () => await httpService.delete('me'),
  getProfile: async () => await httpService.get<User>('me'),

  async updateProfile (data: UpdateCurrentProfileData) {
    Object.assign(this.current, (await httpService.put<User>('me', data)))
  },

  async store (data: CreateUserData) {
    const user = await httpService.post<User>('user', data)
    this.state.users.push(...this.syncWithVault(user))
    return this.byId(user.id)
  },

  async update (user: User, data: UpdateUserData) {
    this.syncWithVault(await httpService.put<User>(`user/${user.id}`, data))
  },

  async destroy (user: User) {
    await httpService.delete(`user/${user.id}`)
    this.state.users = differenceBy(this.state.users, [user], 'id')
    this.vault.delete(user.id)

    // Mama, just killed a man
    // Put a gun against his head
    // Pulled my trigger, now he's dead
    // Mama, life had just begun
    // But now I've gone and thrown it all away
    // Mama, oooh
    // Didn't mean to make you cry
    // If I'm not back again this time tomorrow
    // Carry on, carry on, as if nothing really matters
    //
    // Too late, my time has come
    // Sends shivers down my spine
    // Body's aching all the time
    // Goodbye everybody - I've got to go
    // Gotta leave you all behind and face the truth
    // Mama, oooh
    // I don't want to die
    // I sometimes wish I'd never been born at all
  }
}
