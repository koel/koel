import _ from 'lodash';

import userStore from './user';
import ls from '../services/ls';

export default {
    storeKey: '',

    state: {
        volume: 7,
        notify: true,
        repeatMode: 'NO_REPEAT',
        showExtraPanel: true,
    },

    /**
     * Init the store
     * @param  object user The user whose preferences we are managing.
     */
    init(user = null) {
        if (!user) {
            user = userStore.current();
        }

        this.storeKey = `preferences_${user.id}`;
        _.extend(this.state, ls.get(this.storeKey, this.state));
    },

    set(key, val) {
        this.state[key] = val;
        this.save();
    },

    get(key) {
        return _.has(this.state, key) ? this.state[key] : null;
    },

    save() {
        ls.set(this.storeKey, this.state);
    },
};
