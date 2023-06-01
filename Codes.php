<?php 

namespace SimQ {
    class Codes {
        const CODE_OK = 10;
        const CODE_ERR = 20;
        const CODE_NO_SECURE = 102;
        const CODE_GET_VERSION = 201;

        const CODE_AUTH_GROUP = 1001;
        const CODE_AUTH_PRODUCER = 1003;

        const CODE_GET_CHANNELS = 2001;
        const CODE_GET_CONSUMERS = 2002;
        const CODE_GET_PRODUCERS = 2003;

        const CODE_GET_CHANNEL_LIMIT_MESSAGES = 2101;
        const CODE_ADD_CHANNEL = 3001;
        const CODE_UPDATE_CHANNEL_LIMIT_MESSAGES = 3101;
        const CODE_REMOVE_CHANNEL = 3002;

        const CODE_ADD_CONSUMER = 4001;
        const CODE_REMOVE_CONSUMER = 4002;
        const CODE_UPDATE_CONSUMER_PASSWORD = 4101;

        const CODE_ADD_PRODUCER = 5001;
        const CODE_REMOVE_PRODUCER = 5002;
        const CODE_UPDATE_PROCUCER_PASSWORD = 5101;
    }
}
