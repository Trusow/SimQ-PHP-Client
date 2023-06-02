<?php 

namespace SimQ {
    require_once( './Codes.php' );
    require_once( './Base.php' );

    class Producer extends Base {
        private $length = 0;

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

            if( !$this->sendCmd( Codes::CODE_AUTH_PRODUCER, $authData ) ) return;

            $this->recvCmd();

            $this->_isVerified = true;
        }

        public function createMessage( int $length ) {
            if( !$this->_isVerified ) return false;

            $sendData = [];
            $sendData = $this->packInt( $sendData, $length );
            $this->length = $length;

            if( !$this->sendCmd( Codes::CODE_CREATE_MESSAGE, $sendData ) ) return false;

            $res = $this->recvCmd();
            return $res['data'][0];
        }

        public function createPublicMessage( int $length ) {
            if( !$this->_isVerified ) return false;

            $sendData = [];
            $sendData = $this->packInt( $sendData, $length );
            $this->length = $length;

            if( !$this->sendCmd( Codes::CODE_CREATE_PUBLIC_MESSAGE, $sendData ) ) return false;

            $this->recvCmd();
        }

        public function createReplicateMessage( int $length, string $uuid ) {
            if( !$this->_isVerified ) return false;

            $sendData = [];
            $sendData = $this->packInt( $sendData, $length );
            $sendData = $this->packString( $sendData, $uuid );
            $this->length = $length;

            if( !$this->sendCmd( Codes::CODE_CREATE_REPLICATE_MESSAGE, $sendData ) ) return false;

            $this->recvCmd();
        }

        public function pushMessage( string $data ) {
            $offset = 0;
            while( true ) {
                $this->sendPart( substr( $data, $offset, self::PACKET_SIZE ) );
                $this->recvCmd();

                $offset += self::PACKET_SIZE;

                if( $offset >= $this->length ) {
                    break;
                }
            }
        }
    }
}
