<div class="messenger-sendCard">
    <form id="message-form" method="POST" action="{{ route('send.message') }}" enctype="multipart/form-data">
        @csrf
        <label><span class="fas fa-paperclip"></span><input type="file" class="upload-attachment" name="file" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar,.mp3,.wav,.mp4,.avi,.mov,.mkv" /></label>
        <textarea name="message" class="m-send app-scroll" placeholder="Type a message.."></textarea>
        <button type="submit"><span class="fas fa-paper-plane"></span></button>
    </form>
</div>
