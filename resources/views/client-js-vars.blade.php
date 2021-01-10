<script>
    {{--
        We actually should use a library like dotenv-webpack to load .env variables.
        Unfortunately, can't get it to work for now :(
    --}}
    window.BASE_URL = "{{ asset('') }}";
    window.PUSHER_APP_KEY = "{{ config('broadcasting.connections.pusher.key') }}";
    window.PUSHER_APP_CLUSTER = "{{ config('broadcasting.connections.pusher.options.cluster') }}";
</script>
