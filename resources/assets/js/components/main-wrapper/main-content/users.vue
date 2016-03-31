<template>
    <section id="usersWrapper">
        <h1 class="heading">
            <span>Users
                <i class="fa fa-angle-down toggler"
                    v-show="isPhone && !showingControls"
                    @click="showingControls = true"></i>
                <i class="fa fa-angle-up toggler"
                    v-show="isPhone && showingControls"
                    @click.prevent="showingControls = false"></i>
            </span>

            <div class="buttons" v-show="!isPhone || showingControls">
                <button class="btn btn-green" @click="creating = !creating">
                    <i class="fa fa-plus"></i>
                    Add</button>
            </div>
        </h1>

        <div class="main-scroll-wrap">
            <div class="users">
                <form class="user-create user-item" v-if="creating" @submit.prevent="store">
                    <div class="input-row">
                        <label>Name</label>
                        <input type="text" v-model="newUser.name" required v-koel-focus="creating">
                    </div>
                    <div class="input-row">
                        <label>Email</label>
                        <input type="email" v-model="newUser.email" required>
                    </div>
                    <div class="input-row">
                        <label>Password</label>
                        <input type="password" v-model="newUser.password" required>
                    </div>
                    <div class="input-row">
                        <label></label>
                        <button class="btn btn-green">Create</button>
                        <button class="btn btn-red" @click.prevent="creating = false">Cancel</button>
                    </div>
                </form>

                <user-item v-for="user in state.users" :user="user"></user-item>

                <article class="user-item" v-for="n in 6"></article>
            </div>
        </div>
    </section>
</template>

<script>
    import { clone } from 'lodash';
    import isMobile from 'ismobilejs';

    import userStore from '../../../stores/user';

    import userItem from '../../shared/user-item.vue';

    export default {
        components: { userItem },

        data() {
            return {
                state: userStore.state,
                isPhone: isMobile.phone,
                showingControls: false,
                creating: false,
                newUser: {},
            };
        },

        methods: {
            /**
             * Show the "Create User" form.
             */
            create() {
                this.$root.loadMainView('user-create');
            },

            /**
             * Store the newly created user.
             */
            store() {
                userStore.store(this.newUser.name, this.newUser.email, this.newUser.password, () => {
                    this.newUser = clone(userStore.stub);
                    this.creating = false;
                });
            },
        },
    };
</script>

<style lang="sass">
    @import "../../../../sass/partials/_vars.scss";
    @import "../../../../sass/partials/_mixins.scss";

    #usersWrapper {
        .users {
            justify-content: space-between;
            flex-wrap: wrap;
            display: flex;
        }

        form {
            padding: 8px 16px;
            background-size: 30px 30px;
            background-image: linear-gradient(
                -45deg,
                rgba(black, 0.3)  25%,
                transparent       25%,
                transparent       50%,
                rgba(black, 0.3)  50%,
                rgba(black, 0.3)  75%,
                transparent       75%,
                transparent
            );

            .input-row {
                display: flex;
                margin: 6px 0;
            }

            label {
                flex: 0 0 128px;
                margin-bottom: 0;
                font-size: 100%;
            }

            input {
                flex: 1;
            }
        }

        button {
            font-size: 12px;
            padding: 6px 14px;
            margin-right: 3px;
        }

        @media only screen and (max-width: 768px) {
            .users {
                flex-direction: column;

                .buttons {
                    margin-top: 12px;
                    display: block;
                }
            }
        }
    }

    .user-item {
        width: 32%;
        margin-bottom: 16px;

        .info {
            display: flex;

            img {
                flex: 0 0 128px;
            }

            .right {
                flex: 1;
                padding: 16px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                background-color: rgba(255, 255, 255, .02);
            }

            h1 {
                font-size: 140%;
                margin-bottom: 5px;

                .you {
                    font-size: 14px;
                    color: $colorHighlight;
                    margin-left: 8px;
                }
            }

            .buttons {
                display: none;

                margin-top: 16px;
            }

            &:hover, html.touchevents & {
                .buttons {
                    display: block;
                }
            }
        }

        html.with-extra-panel & {
            width: 49%;
        }

        @media only screen and (max-width: 1024px) {
            width: 100%;
        }
    }
</style>
