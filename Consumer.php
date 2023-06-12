<?php 

namespace SimQ {
    require_once( './Codes.php' );
    require_once( './Base.php' );
    require_once( './Message.php' );

    class Consumer extends Base {
        function __construct(
            string $host,
            int $port,
            string $group,
            string $channel,
            string $login,
            string $password
        ) {
            parent::__construct( $host, $port );
            $this->connectNoSecure();

            $authData = [];
            $authData = $this->packString( $authData, $group );
            $authData = $this->packString( $authData, $channel );
            $authData = $this->packString( $authData, $login );
            $authData = $this->packPassword( $authData, $password );

            if( !$this->sendCmd( Codes::CODE_AUTH_CONSUMER, $authData ) ) return;

            $this->recvCmd();

            $this->_isVerified = true;
        }

        public function popMessage( int $pollDelay = 0 ) {
            $msg = $this->_popMessageMeta( $pollDelay );
            if( $msg->isEmpty() ) return $msg;

            $this->_addDataToMessage( $msg );

            return $msg;
        }

        public function popMessageToPath( string $path, int $pollDelay = 0 ) {
            $msg = $this->_popMessageMeta( $pollDelay );
            if( $msg->isEmpty() ) return $msg;

            $msg->setPath( $path );
            $this->_addDataToMessage( $msg );

            return $msg;
        }

        public function popMessageToFile( $file, int $offset = 0, int $pollDelay = 0 ) {
            $msg = $this->_popMessageMeta( $pollDelay );
            if( $msg->isEmpty() ) return $msg;

            $msg->setFile( $path, $offset );
            $this->_addDataToMessage( $msg );

            return $msg;
        }

        public function removeMessage() {
            if( !$this->_isVerified ) return false;

            if( !$this->sendCmd( Codes::CODE_REMOVE_MESSAGE, [] ) ) return false;

            $this->recvCmd();
        }

        public function removeMessageByUUID( string $uuid ) {
            if( !$this->_isVerified ) return false;

            $sendData = [];
            $sendData = $this->packString( $sendData, $uuid );

            if( !$this->sendCmd( Codes::CODE_REMOVE_MESSAGE_BY_UUID, $sendData ) ) return false;

            $this->recvCmd();
        }

        private function _addDataToMessage( $msg ) {
            $length = $msg->getLength();

            while( true ) {
                if( !$this->sendCmd( Codes::CODE_GET_PART_MESSAGE, [] ) ) return false;
                
                if( $length < self::PACKET_SIZE ) {
                    $msg->addData( $this->recvPart( $length ) );
                    $this->recvCmd();
                    break;
                } else {
                    $msg->addData( $this->recvPart( self::PACKET_SIZE ) );
                    $length -= self::PACKET_SIZE;
                    $this->recvCmd();
                    if( !$length ) break;
                }
            }
        }

        private function _popMessageMeta( int $pollDelay ) {
            if( !$this->_isVerified ) return false;

            $sendData = [];
            $sendData = $this->packInt( $sendData, $pollDelay );

            if( !$this->sendCmd( Codes::CODE_POP_MESSAGE, $sendData ) ) return false;

            $res = $this->recvCmd();
            $msg = new Message();

            switch( $res['cmd'] ) {
                case Codes::CODE_NORMAL_MESSAGE:
                    $msg->setLength( $this->getAsNumber( $res['data'][0] ) );
                    $msg->setUUID( $res['data'][1] );
                    break;
                case Codes::CODE_SIGNAL_MESSAGE:
                    $msg->setLength( $this->getAsNumber( $res['data'][0] ) );
                    break;
            }

            return $msg;
        }
    }
}
