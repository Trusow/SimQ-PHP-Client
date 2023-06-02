<?php 
namespace SimQ {
    require_once( './Codes.php' );

    class Base {
        private $_version = 0;

        private $_isConnect = false;
        private $_socket;
        protected $_isVerified = false;

        private const TYPE_INT = 1;
        private const TYPE_STRING = 2;
        private const TYPE_PASSWORD = 3;

        private const LENGTH_INT = 4;
        private const LENGTH_HASH = 32;

        protected const PACKET_SIZE = 4096;

        private $_sendData = '';
        private $_sendLength = 0;
        private $_fullSendLength = 0;

        function __construct( string $host, int $port ) {
            $this->_socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
            socket_set_option( $this->_socket, SOL_TCP, TCP_NODELAY, true );
            $this->_isConnect = socket_connect( $this->_socket, $host, $port );
        }


        protected function connectNoSecure() {
            if( !$this->isConnect() ) return false;

            if( !$this->sendCmd( Codes::CODE_NO_SECURE, [] ) ) return false;

            $res = $this->recvCmd();

            if( $res === false ) return false;

            if( $res['cmd'] == Codes::CODE_ERR ) throw new \Exception( $res['data'][0] );

            if( !$this->sendCmd( Codes::CODE_GET_VERSION, [] ) ) return false;

            $res = $this->recvCmd();

            if( $res['cmd'] == Codes::CODE_ERR ) throw new \Exception( $res['data'][0] );

            $this->_version = $this->getAsNumber( $res['data'][0] );

            return true;
        }

        protected function getVersion() {
            return $this->_version;
        }

        protected function isConnect() {
            return $this->_isConnect;
        }

        private function _recv( int $length ) {
            if( !$this->isConnect() ) return false;

            return socket_read( $this->_socket, $length );
        }

        protected function recvCmd() {
            if( !$this->isConnect() ) return false;

            $result = [
                'cmd' => 0,
                'data' => [],
            ];

            $res = $this->_recv( self::LENGTH_INT * 2 );

            if( $res === false ) return false;

            $data = unpack( "Ncmd/Nlength", $res );
            $result['cmd'] = $data['cmd'];

            if( $data['length'] == 0 ) return $result;

            $body = $this->_recv( $data['length'] );
            if( $body === false ) return false;

            $offset = 0;

            while( true ) {
                $length = unpack( "Nlength", $body )['length'];

                $offset += self::LENGTH_INT;
                $body = substr( $body, self::LENGTH_INT );

                $result['data'][] = substr( $body, 0, $length );

                $offset += $length;
                $body = substr( $body, $length );

                if( $offset == $data['length'] ) break;
            }

            if( $result['cmd'] == Codes::CODE_ERR ) throw new \Exception( $result['data'][0] );

            return $result;

        }

        protected function getAsNumber( string $str ) {
            return unpack( "Nvalue", $str )['value'];
        }

        private function _send( $data ) {
            if( !$this->isConnect() ) return false;

            $offset = 0;
            $length = strlen( $data );

            while( true ) {
                $l = socket_write(
                    $this->_socket,
                    substr( $data, $offset ),
                    $length - $offset
                );

                if( $l === false ) return false;

                $offset += $l;

                if( $offset == $length ) return true;
            }
        }

        protected function sendCmd( int $cmd, array $data ) {
            if( !$this->isConnect() ) return false;

            $body = '';

            foreach( $data as $item ) {
                $length = $item['length'];

                switch( $item['type'] ) {
                    case self::TYPE_INT:
                        $body .= pack( "N", $length );
                        $body .= pack( "N", $item['value'] );
                        break;
                    case self::TYPE_STRING:
                        $body .= pack( "N", $length + 1 );
                        $body .= $item['value'];
                        $body .= "\0";
                        break;
                    case self::TYPE_PASSWORD:
                        $body .= pack( "N", $length );
                        $body .= $item['value'];
                        break;
                }
            }

            $lengthBody = strlen( $body );

            $meta = pack( "NN", $cmd, $lengthBody );

            if( !$this->_send( $meta ) ) return false;

            return $lengthBody ? $this->_send( $body ) : true;
        }

        protected function recvPart( int $length ) {
            if( !$this->isConnect() ) return false;

            return $this->_recv( $length );
        }

        protected function sendPart( string $data ) {
            if( !$this->isConnect() ) return false;

            return $this->_send( $data );
        }

        protected function packInt( array $data, int $value ) {
            $data[] = [
                'type' => self::TYPE_INT,
                'value' => $value,
                'length' => self::LENGTH_INT,
            ];

            return $data;
        }

        protected function packString( array $data, string $value ) {
            $data[] = [
                'type' => self::TYPE_STRING,
                'value' => $value,
                'length' => strlen( $value ),
            ];
            return $data;
        }

        protected function packPassword( array $data, string $value ) {
            $data[] = [
                'type' => self::TYPE_PASSWORD,
                'value' => hex2bin( hash( 'sha256', $value ) ),
                'length' => self::LENGTH_HASH,
            ];

            return $data;
        }

        public function updatePassword( $currentPassword, $newPassword ) {
            if( !$this->_isVerified ) return false;

            $sendData = [];
            $sendData = $this->packPassword( $sendData, $currentPassword );
            $sendData = $this->packPassword( $sendData, $newPassword );

            if( !$this->sendCmd( Codes::CODE_UPDATE_PASSWORD, $sendData ) ) return false;

            $res = $this->recvCmd();

            if( $res['cmd'] == Codes::CODE_ERR ) throw new \Exception( $res['data'][0] );
        }
    };
}



