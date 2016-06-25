import { http } from '../services';
import stub from '../stubs/settings';

export const settingStore = {
    stub,

    state: {
        settings: [],
    },

    init(settings) {
        this.state.settings = settings;
    },

    get all() {
        return this.state.settings;
    },

    update(successCb = null, errorCb = null) {
        http.post('settings', this.all, successCb, errorCb);
    },
};
