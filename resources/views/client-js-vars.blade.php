<script>
    {{--
        We actually should use a library like dotenv-webpack to load .env variables.
        Unfortunately, can't get it to work for now :(
    --}}
    window.BASE_URL = "{{ asset('') }}";
    window.PUSHER_APP_KEY = "{{ env('PUSHER_APP_KEY') }}";
    window.PUSHER_APP_CLUSTER = "{{ env('PUSHER_APP_CLUSTER') }}";
</script>
