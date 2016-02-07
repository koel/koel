import _ from 'lodash';
import { md5 } from 'blueimp-md5';

import http from '../services/http';
import stub from '../stubs/user';
import sharedStore from './shared';

export default {
    stub,

    state: {
        users: [],
        current: stub,
    },

    /**
     * Init the store.
     *
     * @param {Array.<Object>}  users       The users in the system. Empty array if current user is not an admin.
     * @param {Object}          currentUser The current user.
     */
    init(users, currentUser) {
        this.state.users = users;
        this.state.current = currentUser;

        // Set the avatar for each of the users…
        _.each(this.state.users, this.setAvatar);

        // …and the current user as well.
        this.setAvatar();
    },

    /**
     * Get all users.
     *
     * @return {Array.<Object>}
     */
    all() {
        return this.state.users;
    },

    /**
     * Get a user by his ID
     *
     * @param  {Integer} id
     *
     * @return {Object}
     */
    byId(id) {
        return _.find(this.state.users, {id});
    },

    /**
     * Get or set the current user.
     *
     * @param {?Object} user
     *
     * @return {Object}
     */
    current(user = null) {
        if (user) {
            this.state.current = user;
        }

        return this.state.current;
    },

    /**
     * Set a user's avatar using Gravatar's service.
     *
     * @param {?Object} user The user. If null, the current user.
     */
    setAvatar(user = null) {
        if (!user) {
            user = this.current();
        }

        Vue.set(user, 'avatar', `https://www.gravatar.com/avatar/${md5(user.email)}?s=256`);
    },

    /**
     * Log a user in.
     *
     * @param  {String}     email
     * @param  {String}     password
     * @param  {?Function}  successCb
     * @param  {?Function}  errorCb
     */
    login(email, password, successCb = null, errorCb = null) {
        http.post('me', { email, password }, successCb, errorCb);
    },

    /**
     * Log the current user out.
     *
     * @param  {Function} cb The callback.
     */
    logout(cb = null) {
        http.delete('me', {}, () => {
            if (cb) {
                cb();
            }
        });
    },

    /**
     * Update the current user's profile.
     *
     * @param  {string} password Can be an empty string if the user is not changing his password.
     * @param  {?Function}  successCb
     * @param  {?Function}  errorCb
     */
    updateProfile(password = null, cb = null) {
        http.put('me', {
                password,
                name: this.current().name,
                email: this.current().email
            }, () => {
                this.setAvatar();

                if (cb) {
                    cb();
                }
            }
        );
    },

    /**
     * Stores a new user into the database.
     *
     * @param  {string}     name
     * @param  {string}     email
     * @param  {string}     password
     * @param  {?Function}  cb
     */
    store(name, email, password, cb = null) {
        http.post('user', { name, email, password }, response => {
            var user = response.data;

            this.setAvatar(user);
            this.state.users.push(user);

            if (cb) {
                cb();
            }
        });
    },

    /**
     * Update a user's profile.
     *
     * @param  {Object}     user
     * @param  {String}     name
     * @param  {String}     email
     * @param  {String}     password
     * @param  {?Function}  cb
     */
    update(user, name, email, password, cb = null) {
        http.put(`user/${user.id}`, { name, email, password }, () => {
            this.setAvatar(user);
            user.password = '';

            if (cb) {
                cb();
            }
        });
    },

    /**
     * Delete a user.
     *
     * @param  {Object}     user
     * @param  {?Function}  cb
     */
    destroy(user, cb = null) {
        http.delete(`user/${user.id}`, {}, () => {
            this.state.users = _.without(this.state.users, user);

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

            /**
             * Brian May enters the stage.
             */
            if (cb) {
                cb();
            }
        });
    },
};
