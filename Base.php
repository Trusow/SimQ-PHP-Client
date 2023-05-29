<?php 

namespace SimQ {
    class Base {
        private $_version = 0;

        private $_isConnect = false;
        private $_socket;
        private $_isVerified = false;

        private const CODE_NO_SECURE = 102;
        private const CODE_GET_VERSION = 201;
        private const CODE_OK = 10;
        private const CODE_ERR = 20;

        private const TYPE_INT = 1;
        private const TYPE_STRING = 2;
        private const TYPE_PASSWORD = 3;

        private const LENGTH_INT = 4;
        private const LENGTH_HASH = 32;

        private $_sendData = '';
        private $_sendLength = 0;
        private $_fullSendLength = 0;

        function __construct( string $host, int $port ) {
            $this->_socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
            $this->_isConnect = socket_connect( $this->_socket, $host, $port );
        }


        protected function connectNoSecure() {
            if( $this->_isVerified ) return false;
            if( !$this->isConnect() ) return false;

            if( !$this->sendCmd( self::CODE_NO_SECURE, [] ) ) return false;

            $res = $this->recvCmd();

            if( $res === false ) return false;

            if( $res['cmd'] == self::CODE_ERR ) throw new \Exception( $res['data'][0] );

            if( !$this->sendCmd( self::CODE_GET_VERSION, [] ) ) return false;

            $res = $this->recvCmd();

            if( $res['cmd'] == self::CODE_ERR ) throw new \Exception( $res['data'][0] );

            $this->_version = $this->getAsNumber( $res['data'][0] );

            $this->_isVerified = true;

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

            while( true ) {
                $itemLength = $this->_recv( self::LENGTH_INT );

                if( $itemLength === false ) return false;

                $length =unpack( "Nlength", $itemLength )['length'];
                $data['length'] -= self::LENGTH_INT + $length;

                $item = $this->_recv( $length );

                if( $item === false ) return false;

                $result['data'][] = $item;

                if( !$data['length'] ) break;
            }

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
                $body .= pack( "N", $length );

                switch( $item['type'] ) {
                    case self::TYPE_INT:
                        $body .= pack( "N", $item['value'] );
                        break;
                    default:
                        $body .= $item['value'];
                        break;
                }
            }

            $lengthBody = strlen( $body );

            $meta = pack( "NN", $cmd, $lengthBody );

            if( !$this->_send( $meta ) ) return false;

            if( $lengthBody ) return $this->_send( $lengthBody );

            return true;
        }

        protected function recvPart( int $length ) {
            if( !$this->isConnect() ) return false;
        }

        protected function sendPart( string $data ) {
            if( !$this->isConnect() ) return false;
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



    };
}



