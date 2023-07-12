<?php 

namespace SimQ;

require_once( 'Codes.php' );

class Message {
    private string $uuid = '';
    private int $length = 0;
    private int $offset = 0;
    private $file = null;
    private $data = '';
    private $signal = false;

    public function setLength( int $length ) {
        $this->length = $length;
    }

    public function getLength() {
        return $this->length;
    }

    public function isEmpty() {
        return $this->length === 0;
    }

    public function setUUID( string $uuid ) {
        $this->uuid = $uuid;
    }

    public function getUUID( string $uuid ) {
        return $this->uuid;
    }

    public function setSignal() {
        $this->signal = true;
    }

    public function isSignal() {
        return $this->signal;
    }

    public function setPath( string $path ) {
        $this->file = fopen( $path, 'w' );
        fseek( $this->file, $this->offset );
    }

    public function setFile( $file, int $offset ) {
        $this->file = $file;
        fseek( $this->file, $offset );
        $this->offset = $offset;
    }

    public function addData( string $data ) {
        if( $this->file === null ) {
            $this->data .= $data;
            return;
        }

        $length = strlen( $data );
        fwrite( $this->file, $data, $length );
        $this->offset += $length;
        fseek( $this->file, $this->offset );
    }

    public function getData() {
        return $this->data;
    }
}
