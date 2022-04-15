import { without } from 'lodash'
import md5 from 'blueimp-md5'

import { http } from '@/services'
import { alerts } from '@/utils'
import stub from '@/stubs/user'
import { reactive } from 'vue'

export interface UpdateCurrentProfileData {
  current_password: string|null
  name: string
  email: string
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
  stub,

  state: reactive({
    users: [] as User[],
    current: stub
  }),

  init (users: User[], currentUser: User): void {
    this.all = users
    this.current = currentUser

    // Set the avatar for each of the users…
    this.all.forEach(user => this.setAvatar(user))

    // …and the current user as well.
    this.setAvatar()
  },

  get all (): User[] {
    return this.state.users
  },

  set all (value: User[]) {
    this.state.users = value
  },

  byId (id: number): User {
    return <User> this.all.find(user => user.id === id)
  },

  get current () {
    return this.state.current
  },

  set current (user: User) {
    this.state.current = user
  },

  /**
   * Set a user's avatar using Gravatar's service.
   *
   * @param {?User} user The user. If null, the current user.
   */
  setAvatar (user?: User): void {
    user = user || this.current
    user.avatar = `https://www.gravatar.com/avatar/${md5(user.email)}?s=256&d=mp`
  },

  login: async (email: string, password: string): Promise<User> => {
    return await http.post<User>('me', { email, password })
  },

  logout: async (): Promise<void> => {
    await http.delete('me')
  },

  getProfile: async (): Promise<User> => {
    return await http.get<User>('me')
  },

  async updateProfile (data: UpdateCurrentProfileData): Promise<void> {
    await http.put('me', data)

    this.current.name = data.name
    this.current.email = data.email
    this.setAvatar()

    alerts.success('Profile updated.')
  },

  async store (data: CreateUserData): Promise<User> {
    const user = await http.post<User>('user', data)
    this.setAvatar(user)
    this.all.unshift(user)
    alerts.success(`New user "${data.name}" created.`)

    return user
  },

  async update (user: User, data: UpdateUserData): Promise<void> {
    await http.put(`user/${user.id}`, data)
    this.setAvatar(user)
    ;[user.name, user.email, user.is_admin] = [data.name, data.email, data.is_admin]
    alerts.success('User profile updated.')
  },

  async destroy (user: User): Promise<void> {
    await http.delete(`user/${user.id}`)
    this.all = without(this.all, user)
    alerts.success(`User "${user.name}" deleted.`)

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
