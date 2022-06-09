<template>
    <div class="user-chat row">
        <div class="message-area col-md-12" ref="message">
            <div :key="message.id" v-for="message in messages" :class="{'message': true, 'text-right' : (message.sender_id === auth.id) }" >
                <strong class="user">{{ message.sender.name }}</strong>
                <p class="message">{{ message.message }}</p>
            </div>
        </div>
        <div class="col-md-10">
            <input type="text" class="form-control" v-model="newMessage" />
        </div>
        <div class="col-md-2">
            <button class="btn btn-success" @click="sendMessage">Send</button>
        </div>

    </div>


</template>

<script>
    export default {
        name: "PrivateMessage",
        props:["auth","receiver"],
        data() {
            return {
                newMessage: null,
                messages: []
            }
        },
        created(){
            if (this.receiver){
                this.getMessages();
                window.Echo.private(`message.sent.` + this.receiver.id).listen('MessageSent', (e) => {
                    console.log('www');
                    this.messages.push(e.message);
                });
            }

        },

        methods:{

            getMessages(){
                let data = {
                    'receiver_id' : this.receiver.id,
                };
                axios.post('/get/messages', data, {})
                    .then(response => {
                        this.messages = response.data.messages;
                    }).catch(error => {
                });
            },
            sendMessage(){
                if (this.newMessage.trim() == '') {
                    return false;
                }
                const data = new FormData();
                data.append('message', this.newMessage);
                data.append('receiver_id', this.receiver.id);

                axios.post('/send/message', data, {})
                    .then(response => {
                        this.messages.push(response.data.message);
                        // this.showMessageToReceiver();
                    }).catch(error => {

                });

            },
        }
    }
</script>

<style scoped>
    .message-area {
        height: 400px;
        max-height: 400px;
        overflow-y: scroll;
        padding: 15px;
        border-bottom: 1px solid #eee;
    }

    .user {
        font-weight: 800;
    }
    .message {
        margin-bottom: 0;
        white-space: pre-wrap;
    }

    .text-right {
        text-align: right;
    }


    .self {
        background-color: #f0f0f0;
        padding: 10px;
    }
</style>
