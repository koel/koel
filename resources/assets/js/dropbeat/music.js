export default {
    Music: function (params) {
        var that = this;

        that.id = params.id;
        that.title = params.title;
        that.type = params.type;
    },

    MusicQueue:{
        state: {
            q: [],
            playType: 'normal',
        },

        init(listOfMusic) {
            var that  = this;

            delete that.state.q;
            that.state.q = [];
            if (listOfMusic) {
                that.state.q = that.state.q.concat(listOfMusic);
            }
        },

        push(music) {
            var that = this;
            that.state.q.push(music);
        },

// url로 곡 추가하는 기능이다
//         pushEOL() {
//             var that = this;
// // this method is needed for indicating stopping music iterating
// // Music play stops when EOL popped from queue.
//             that.state.q.push(module.constants.queueEOL);
//         },

        pop() {
            var that = this;
            if (that.state.playType === 'normal') {
                return that.state.q.shift();
            }
        },

        top() {
            var that = this;

            if (that.state.q.length !== 0) {
                return that.state.q[0];
            }
        },

        removeWithId(musicId) {
            var i, that = this;

            for (i = 0; i < that.state.q.length; i += 1) {
                if (musicId === that.state.q[i].id) {
                    that.state.q.splice(i, 1);
                }
            }
        },
    },
};
