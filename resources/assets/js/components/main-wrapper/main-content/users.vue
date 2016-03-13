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
                <button class="btn-create" @click="creating = !creating">
                    <i class="fa fa-plus"></i>
                    Add</button>
            </div>
        </h1>

        <div class="main-scroll-wrap">
            <form class="user-create" v-show="creating" @submit.prevent="store">
                <div class="input-col">
                    <label>Name</label>
                    <input type="text" v-model="newUser.name" required v-koel-focus="creating">
                </div>
                <div class="input-col">
                    <label>Email</label>
                    <input type="email" v-model="newUser.email" required>
                </div>
                <div class="input-col">
                    <label>Password</label>
                    <input type="password" v-model="newUser.password" required>
                </div>
                <div class="btn-col">
                    <button>Create</button>
                    <button class="cancel" @click.prevent="creating = false">Cancel</button>
                </div>
            </form>

            <form class="user-edit" v-show="editedUser.id" @submit.prevent="update(editedUser)">
                <div class="input-col">
                    <label>Name</label>
                    <input type="text" v-model="editedUser.name" required v-koel-focus="editedUser.id">
                </div>
                <div class="input-col">
                    <label>Email</label>
                    <input type="email" v-model="editedUser.email" required>
                </div>
                <div class="input-col">
                    <label>Password</label>
                    <input type="password" v-model="editedUser.password" placeholder="Leave blank for no changes">
                </div>
                <div class="btn-col">
                    <button>Update</button>
                    <button class="cancel" @click.prevent="cancelEdit">Cancel</button>
                </div>
            </form>

            <div class="users">
                <article v-for="user in state.users" class="user-item" :class="{ editing: editedUser === user }">
                    <img :src="user.avatar" width="128" height="128" alt="">

                    <div class="right">
                        <div class="info">
                            <h1>{{ user.name }}
                                <i v-if="user.id === state.current.id" class="you fa fa-check-circle"></i>
                            </h1>

                            <p>{{ user.email }}</p>
                        </div>

                        <div class="buttons" v-show="editedUser !== user">
                            <button class="edit" @click="edit(user)" v-show="deletedUser !== user">
                                {{ user.id === state.current.id ? 'Update Profile' : 'Edit' }}
                            </button>
                            <button v-if="user.id !== state.current.id && deletedUser !== user"
                                class="delete"
                                @click="confirmDelete(user)">Delete
                            </button>
                            <span v-show="deletedUser === user">
                                <button class="delete" @click="destroy(user)">Confirm</button>
                                <button @click="cancelDelete(user)">Cancel</button>
                            </span>
                        </div>
                    </div>
                </article>

                <article class="user-item" v-for="n in 6"></article>
            </div>
        </div>
    </section>
</template>

<script>
    import _ from 'lodash';
    import isMobile from 'ismobilejs';

    import userStore from '../../../stores/user';

    export default {
        data() {
            return {
                state: userStore.state,
                isPhone: isMobile.phone,
                showingControls: false,
                creating: false,
                newUser: _.clone(userStore.stub),
                editedUser: _.clone(userStore.stub),
                deletedUser: _.clone(userStore.stub),
                cached: null,
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
             * Show the "Edit User" form.
             *
             * @param {Object} user
             */
            edit(user) {
                if (user.id === this.state.current.id) {
                    this.$root.loadMainView('profile');
                } else {
                    // Keep a cached version of the user for rollback.
                    this.cached = _.clone(user);
                    this.editedUser = user;
                }
            },

            /**
             * Cancel editing, or simply close the form after updating.
             * @param  {Boolean=true} rollback If true, editing was cancelled.
             *                                 The original state of the edited user must be restored.
             *                                 If false, editing was successfully committed.
             */
            cancelEdit(rollback = true) {
                if (rollback) {
                    // Find the original user and roll it back
                    _.extend(userStore.byId(this.editedUser.id), this.cached);
                    this.cached = null;
                }

                this.editedUser = _.clone(userStore.stub);
            },

            /**
             * Store the newly created user.
             */
            store() {
                userStore.store(this.newUser.name, this.newUser.email, this.newUser.password, () => {
                    this.newUser = _.clone(userStore.stub);
                    this.creating = false;
                    // TODO: Scroll to bottom?
                });
            },

            /**
             * Update the edited user.
             */
            update() {
                userStore.update(this.editedUser,
                    this.editedUser.name,
                    this.editedUser.email,
                    this.editedUser.password, () => {
                        this.cancelEdit(false);
                        // TODO: Scroll to the user?
                    }
                );
            },

            /**
             * Show the controls to really delete a user or cancel deleting.
             *
             * @param {Object} user
             */
            confirmDelete(user) {
                this.deletedUser = user;
            },

            /**
             * Cancel deleting. The confirmation will be closed.
             *
             * @param {Object} user
             */
            cancelDelete(user) {
                this.deletedUser = _.clone(userStore.stub);
            },

            /**
             * Delete a user.
             *
             * @param {Object} user
             */
            destroy(user) {
                userStore.destroy(user, () => {
                    // Set our data to a stub to avoid any reference errors.
                    this.deletedUser = _.clone(userStore.stub);
                });
            },
        },
    };
</script>

<style lang="sass">
    @import "../../../../sass/partials/_vars.scss";
    @import "../../../../sass/partials/_mixins.scss";

    @keyframes barberpole {
        from { background-position: 0 0; }
        to   { background-position: 60px 30px; }
    }

    #usersWrapper {
        .users {
            justify-content: space-between;
            flex-wrap: wrap;
            display: flex;

            .user-item {
                display: flex;
                flex: 0 0 376px;
                margin-bottom: 16px;

                &.editing {
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

                    animation: barberpole 2s linear infinite;
                }

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
                }

                &:hover {
                    .buttons {
                        display: block;
                    }
                }

                button {
                    font-size: 12px;
                    padding: 6px 14px;
                    background: transparent;
                    border: 1px solid rgba(255, 255, 255, .1);

                    &.edit:hover {
                        background-color: $colorBlue;
                    }

                    &.delete:hover {
                        background-color: $colorRed;
                    }
                }
            }
        }

        .btn-create {
            background: $colorGreen !important;

            &:hover {
                background: darken($colorGreen, 10%) !important;
            }
        }

        form.user-create, form.user-edit {
            display: flex;
            align-items: flex-end;
            margin-bottom: 32px;
            padding-bottom: 32px;
            border-bottom: 1px solid rgba(255, 255, 255, .1);

            .input-col {
                flex: 1;
                padding-right: 8px;

                input {
                    height: 32px;
                    width: 100%;
                }
            }

            button {
                height: 32px;
                padding-top: 6px;

                &.cancel {
                    background: $colorRed;
                }
            }
        }

        @media only screen and (max-device-width : 667px) {
            form.user-create, form.user-edit {
                flex-direction: column;
                align-items: stretch;

                .input-col {
                    padding-right: 0;
                }

                .input-col, .btn-col {
                    margin-top: 12px;
                }
            }

            .users {
                flex-direction: column;

                .user-item {
                    flex: 1;

                    img {
                        display: none;
                    }

                    .buttons {
                        margin-top: 12px;
                        display: block;
                    }
                }
            }
        }

        @media only screen
        and (min-device-width : 668px)
        and (max-device-width : 768px) {
            .users {
                flex-direction: column;

                .user-item {
                    flex: 1;
                }

                .buttons {
                    margin-top: 12px;
                    display: block;
                }
            }
        }
    }
</style>
