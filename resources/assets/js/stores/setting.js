import http from '../services/http';
import stub from '../stubs/settings';

export default {
    stub,
    
    state: {
        settings: [], 
    },
    
    init(settings) {
        this.state.settings = settings;
    },

    all() {
        return this.state.settings;
    },

    update(successCb = null, errorCb = null) {
        http.post('settings', this.all(), successCb, errorCb);
    },
};
