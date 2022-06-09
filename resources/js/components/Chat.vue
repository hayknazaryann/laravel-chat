<template>
    <div class="row">

        <div class="col-md-3">
            <div class="users-list">
                <ul class="list-unstyled chat-list mt-2 mb-0">
                    <li class="clearfix" :class="{'active' : isActive}" v-for="user in users" :id="'user-'+user.id" @click="openChat(user.id)">
                        <div class="about">
                            <div class="name">{{user.name}}</div>
                            <div class="status"> <i class="fa fa-circle offline"></i> Offline </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-md-9">
            <div class="user-chat row" v-if="this.receiver">
                <div class="message-area col-md-12" ref="message">
                    <div :key="message.id" v-for="message in messages" :class="{'row' : true, 'message-out text-right justify-content-end': (message.sender_id === auth.id), 'message-in': !(message.sender_id === auth.id)}" >
                        <div class="col-md-6">
                            <p class="user">{{ message.sender.name }}</p>
                            <div class="message">{{ message.message }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-10">
                    <input type="text" class="form-control" v-model="newMessage" />
                </div>
                <div class="col-md-2">
                    <button class="btn btn-success" @click="sendMessage">Send</button>
                </div>

            </div>
            <!--<private-message  :auth="auth" :receiver="this.receiver"></private-message>-->
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
                isActive: false
            };
        },
        created(){
            this.getUsers();
        },
        mounted(){
            window.Echo.channel(`user.activity`).listen('UsersActivity', (e) => {
                let user_row = document.getElementById('user-' + e.user_id);
                if (user_row){
                    let status_row = user_row.getElementsByClassName('status')[0];
                    status_row.lastChild.textContent = "Online";
                    user_row.getElementsByTagName('i')[0].classList.remove('offline');
                    user_row.getElementsByTagName('i')[0].classList.add('online');
                }
            });
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
                        this.messageSent();
                    }).catch(error => {
                });
                this.isActive = true;


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

            messageSent(){
                window.Echo.private(`message.sent.` + this.chat.id).listen('MessageSent', (e) => {
                    this.messages.push(e.message);
                });
            },

            listen() {
                window.Echo.join(`private.chat`)
                    .here(users => {
                        this.friends.forEach(friend => {
                            users.forEach(user => {
                                if (user.id == friend.id) {
                                    friend.online = true;
                                }
                            });
                        });
                    })
                    .joining(user => {
                        this.friends.forEach(
                            friend => (user.id == friend.id ? (friend.online = true) : "")
                        );
                    })
                    .leaving(user => {
                        this.friends.forEach(
                            friend => (user.id == friend.id ? (friend.online = false) : "")
                        );
                    });
            }
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

    .text-right {
        text-align: right;
    }


    .self {
        background-color: #f0f0f0;
        padding: 10px;
    }
</style>
