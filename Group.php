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
    }
}
