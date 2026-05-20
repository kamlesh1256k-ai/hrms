<script src="https://js.pusher.com/7.0.3/pusher.min.js"></script>
<script>
    // Enable pusher logging - don't include this in production
    Pusher.logToConsole = true;

    var chatifyPusherKey = "{{ config('chatify.pusher.key') }}";
    var chatifyPusherCluster = "{{ config('chatify.pusher.options.cluster') }}";

    function createNoopPusher() {
        return {
            subscribe: function() {
                return {
                    bind: function() {},
                    trigger: function() {
                        return true;
                    }
                };
            },
            connection: {
                bind: function() {}
            }
        };
    }

    var pusher = createNoopPusher();
    window.chatifyPusherEnabled = false;
    if (chatifyPusherKey && chatifyPusherCluster) {
        pusher = new Pusher(chatifyPusherKey, {
            encrypted: true,
            cluster: chatifyPusherCluster,
            authEndpoint: '{{ route('pusher.auth') }}',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            }
        });
        window.chatifyPusherEnabled = true;
    }

    // Bellow are all the methods/variables that using php to assign globally.
    const allowedImages = {!! json_encode(config('chatify.attachments.allowed_images')) !!} || [];
    const allowedFiles = {!! json_encode(config('chatify.attachments.allowed_files')) !!} || [];
    const getAllowedExtensions = [...allowedImages, ...allowedFiles];
    const getMaxUploadSize = {{ Chatify::getMaxUploadSize() }};
</script>
<script src="{{ asset('js/chatify/code.js') }}?v={{ @filemtime(public_path('js/chatify/code.js')) }}"></script>
