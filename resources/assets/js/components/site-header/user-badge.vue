<template>
    <span class="profile" id="userBadge">
        <span class="view-profile control" @click="loadMainView('profile')">
            <img class="avatar" :src="state.current.avatar" alt="Avatar"></img>
            <span class="name">{{ state.current.name }}</span>
        </span>

        <a class="logout" @click.prevent="logout"><i class="fa fa-sign-out control"></i></a>
    </span>
</template>

<script>
    import userStore from '../../stores/user';
    import { event, loadMainView } from '../../utils';

    export default {
        data() {
            return {
                state: userStore.state,
            };
        },

        methods: {
            loadMainView(v) {
                loadMainView(v);
            },

            logout() {
                event.emit('logout');
            },
        },
    };
</script>

<style lang="sass">
    @import "../../../sass/partials/_vars.scss";
    @import "../../../sass/partials/_mixins.scss";

    #userBadge {
        @include vertical-center();
        justify-content: flex-end;
        flex: 0 0 $extraPanelWidth;
        padding-right: 16px;
        text-align: right;

        .avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .view-profile {
            margin-right: 16px;
            @include vertical-center();
        }

        @media only screen and (max-width : 667px) {
            flex: 0 0 96px;
            margin-right: 0;
            padding-right: 0;
            align-content: stretch;

            .name {
                display: none;
            }

            .view-profile, .logout {
                flex: 0 0 40px;
                font-size: 1.4rem;
                margin-right: 0;

                @include vertical-center();
            }
        }
    }
</style>
