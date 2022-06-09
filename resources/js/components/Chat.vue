<template>
    <div class="row">

        <div class="col-md-3">
            <div class="users-list">
                <ul class="list-unstyled chat-list mt-2 mb-0">
                    <li class="clearfix"  :class="{'active' : (user.id === isActive)}" v-for="user in users" :id="'user-'+user.id" @click="openChat(user.id)">
                        <div class="about">
                            <div class="name">{{user.name}}</div>
                            <div class="status">
                                <i class="fa fa-circle" v-bind:class="(onlineUsers.find(onlineUser => onlineUser.id===user.id)) ? 'online' : 'offline'"></i>
                                {{(onlineUsers.find(onlineUser => onlineUser.id===user.id)) ? 'Online' : 'Offline'}}
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-md-9">
            <div class="row  receiver-name" v-if="this.receiver">
                <div class="col-md-12">
                    <h5 class="m-auto">{{this.receiver.name}}</h5>
                </div>
            </div>
            <div class="user-chat row justify-content-center" v-if="this.receiver">
                <div class="message-area col-md-12" ref="message">
                    <div :key="message.id" v-for="message in messages" :class="{'row mt-2' : true, 'message-out text-right justify-content-end': (message.sender_id === auth.id), 'message-in': !(message.sender_id === auth.id)}" >
                        <div class="col-md-6">
                            <div class="message">{{ message.message }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <input type="text" class="form-control" v-model="newMessage" />
                </div>
                <div class="col-md-2">
                    <button class="btn btn-success" @click="sendMessage">Send</button>
                </div>

            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "Chat",
        props: ["auth"],
        data() {
            return {
                users: [],
                onlineUsers : [],
                chat : null,
                chat_id : null,
                receiver : null,
                newMessage: null,
                messages: [],
                isActive: null,
            };
        },
        created(){
            this.getUsers();
            window.Echo.join(`user.activity`)
                .here(onlineUsers => {
                    this.onlineUsers = onlineUsers;
                })
                .joining(onlineUser => {
                    this.onlineUsers.push(onlineUser);
                })
                .leaving(onlineUser => {
                    this.onlineUsers.splice(this.onlineUsers.findIndex(e => e.id === onlineUser.id),1);
            });
        },
        mounted(){

        },
        methods: {
            getUsers(){
                axios.get('/get/users', {}, {})
                    .then(response => {
                        this.users = response.data.users;
                    }).catch(error => {
                });
            },

            openChat(user_id) {
                let data = {
                    'receiver_id' : user_id,
                };

                axios.post('/open/chat', data, {})
                    .then(response => {
                        this.chat = response.data.chat;
                        this.chat_id = this.chat.id;
                        this.messages = response.data.messages;
                        this.receiver = response.data.receiver;
                        this.messageSentEvent();
                    }).catch(error => {
                });
                this.isActive = user_id;
            },

            sendMessage(){
                if (this.newMessage.trim() == '') {
                    return false;
                }
                const data = new FormData();
                data.append('message', this.newMessage);
                data.append('receiver_id', this.receiver.id);
                data.append('chat_id', this.chat.id);


                axios.post('/send/message', data, {})
                    .then(response => {
                        this.messages.push(response.data.message);

                    }).catch(error => {
                });
                this.newMessage = '';
            },

            messageSentEvent(){
                window.Echo.private(`message.sent.${this.chat.id}`).listen('MessageSent', (e) => {
                    this.messages.push(e.message);
                });
            },
        },
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

    .message {
        margin-bottom: 0;
        white-space: pre-wrap;
        background: #f6f9fb;
        border-radius: 0.6rem;
        padding: 1rem 1.25rem;
        color: #95aac9;
    }

    .message-out .message {
        background: #2787f5;
        border-radius: 0.6rem;
        color: #fff;
    }

</style>
