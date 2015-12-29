<template>
    <form @submit.prevent="login">
        <input v-model="email" type="email" name="email" placeholder="Email Address" autofocus required>
        <input v-model="password" type="password" name="password" placeholder="Password" required>
        <button type="submit">Log In</button>
    </form>
</template>

<script>
    import userStore from '../../stores/user';

    export default {
        data() {
            return {
                email: '',
                password: '',
            };
        },

        methods: {
            login() {
                userStore.login(this.email, this.password, () => {
                    // Emit the event
                });
            },
        },
    };
</script>

<style lang="sass" scoped>
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";
    @import "resources/assets/sass/partials/_shared.scss";

    form {
        width: 300px;
        padding: 24px;
        background: rgba(255,255,255,.08);
        border-radius: 8px;
        border: 1px solid #333;
    }

    form.error {
        border-color: #8e4947;
    }

    form:before {
        content: " ";
        display: block;
        background: url(/public/img/logo.svg) center top no-repeat;
        background-size: 156px;
        height: 172px;
    }

    input {
        display: block;
        margin-top: 12px;
        padding: 8px;
        border: 0;
        background: #fff;
        outline: none;
        width: 100%;
        border-radius: 3px;
    }

    button {
        display: block;
        margin-top: 12px;
        width: 100%;
    }
</style>
