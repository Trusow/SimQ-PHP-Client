<?php 

namespace SimQ {
    require_once( './Codes.php' );
    require_once( './Base.php' );

    class Producer extends Base {
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

            $res = $this->recvCmd();
            if( $res['cmd'] == Codes::CODE_ERR ) throw new \Exception( $res['data'][0] );

            $this->_isVerified = true;
        }
    }
}
