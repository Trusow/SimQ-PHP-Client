<?php 

namespace SimQ {
    require_once( './Codes.php' );
    require_once( './Base.php' );

    class Group extends Base {
        private $_isVerified = false;

        function __construct( string $host, int $port, string $group, string $password ) {
            parent::__construct( $host, $port );
            $this->connectNoSecure();


            $authData = [];
            $authData = $this->packString( $authData, $group );
            $authData = $this->packPassword( $authData, $password );

            if( !$this->sendCmd( Codes::CODE_AUTH_GROUP, $authData ) ) return;

            $res = $this->recvCmd();
            if( $res['cmd'] == Codes::CODE_ERR ) throw new \Exception( $res['data'][0] );

            $_isVerified = true;
        }

        public function getChannels() {
            if( $this->_isVerified ) return false;

            if( !$this->sendCmd( Codes::CODE_GET_CHANNELS, [] ) ) return false;

            $res = $this->recvCmd();

            if( $res['cmd'] == Codes::CODE_ERR ) throw new \Exception( $res['data'][0] );

            return $res['data'];

        }

        public function getChannelLimitMessages( string $channel ) {
            if( $this->_isVerified ) return false;

            $sendData = [];
            $sendData = $this->packString( $sendData, $channel );

            if( !$this->sendCmd( Codes::CODE_GET_CHANNEL_LIMIT_MESSAGES, $sendData ) ) return false;

            $res = $this->recvCmd();

            if( $res['cmd'] == Codes::CODE_ERR ) throw new \Exception( $res['data'][0] );

            $data = $res['data'];

            return [
                'minMessageSize' => $this->getAsNumber( $data[0] ),
                'maxMessageSize' => $this->getAsNumber( $data[1] ),
                'maxMessagesInMemory' => $this->getAsNumber( $data[2] ),
                'maxMessagesOnDisk' => $this->getAsNumber( $data[3] ),
            ];
        }

        public function addChannel( string $channel, array $limitMessages ) {
            if( $this->_isVerified ) return false;

            $sendData = [];
            $sendData = $this->packString( $sendData, $channel );
            $sendData = $this->packInt( $sendData, $limitMessages['minMessageSize'] );
            $sendData = $this->packInt( $sendData, $limitMessages['maxMessageSize'] );
            $sendData = $this->packInt( $sendData, $limitMessages['maxMessagesInMemory'] );
            $sendData = $this->packInt( $sendData, $limitMessages['maxMessagesOnDisk'] );

            if( !$this->sendCmd( Codes::CODE_ADD_CHANNEL, $sendData ) ) return false;

            $res = $this->recvCmd();

            if( $res['cmd'] == Codes::CODE_ERR ) throw new \Exception( $res['data'][0] );
        }

        public function updateChannelLimitMessages( string $channel, array $limitMessages ) {
            if( $this->_isVerified ) return false;

            $sendData = [];
            $sendData = $this->packString( $sendData, $channel );
            $sendData = $this->packInt( $sendData, $limitMessages['minMessageSize'] );
            $sendData = $this->packInt( $sendData, $limitMessages['maxMessageSize'] );
            $sendData = $this->packInt( $sendData, $limitMessages['maxMessagesInMemory'] );
            $sendData = $this->packInt( $sendData, $limitMessages['maxMessagesOnDisk'] );

            if( !$this->sendCmd( Codes::CODE_UPDATE_CHANNEL_LIMIT_MESSAGES, $sendData ) ) return false;

            $res = $this->recvCmd();

            if( $res['cmd'] == Codes::CODE_ERR ) throw new \Exception( $res['data'][0] );
        }

        public function removeChannel( string $channel ) {
            if( $this->_isVerified ) return false;

            $sendData = [];
            $sendData = $this->packString( $sendData, $channel );

            if( !$this->sendCmd( Codes::CODE_REMOVE_CHANNEL, $sendData ) ) return false;

            $res = $this->recvCmd();

            if( $res['cmd'] == Codes::CODE_ERR ) throw new \Exception( $res['data'][0] );
        }
    }
}
