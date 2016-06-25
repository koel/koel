import Vue from 'vue';
import App from './app.vue'
import ls from './services/ls';
import NProgress from 'nprogress';
import { event } from './utils';

Vue.config.debug = false;
Vue.use(require('vue-resource'));
Vue.http.options.root = '/api';
Vue.http.interceptors.push({
    request(r) {
        const token = ls.get('jwt-token');

        if (token) {
            Vue.http.headers.common.Authorization = `Bearer ${token}`;
        }

        return r;
    },

    response(r) {
        NProgress.done();

        if (r.status === 400 || r.status === 401) {
            if (!(r.request.method === 'POST' && r.request.url === 'me')) {
                // This is not a failed login. Log out then.
                event.emit('logout');
            }
        }

        if (r.headers && r.headers.Authorization) {
            ls.set('jwt-token', r.headers.Authorization);
        }

        if (r.data && r.data.token && r.data.token.length > 10) {
            ls.set('jwt-token', r.data.token);
        }

        return r;
    },
});

/**
 * Thor! Odin's son, protector of Mankind
 * Ride to meet your fate, your destiny awaits
 * Thor! Hlödyn's son, protector of Mankind
 * Ride to meet your fate, Ragnarök awaits
 */
new Vue({
    el: '#app',
    render: h => h(App),
    created() { event.init() },
});
