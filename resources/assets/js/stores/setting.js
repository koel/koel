import http from '../services/http';
import stub from '../stubs/settings';
import sharedStore from './shared';

export default {
    stub,
    
    state: {
        settings: [], 
    },
    
    init() {
        this.state.settings = sharedStore.state.settings;
    },

    all() {
        return this.state.settings;
    },

    update(cb = null, error = null) {
        http.post('settings', this.all(), msg => {
            if (cb) {
                cb();
            }
        }, { error });
    },
};
