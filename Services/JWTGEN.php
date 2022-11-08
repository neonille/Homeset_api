<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTGEN {

    public static function GENERATE($id){
        $payload = [
        'iss' => 'http://example.org',
        'exp' =>  time() + 60 * 60,
        'id' => $id
        ];
        $jwt = JWT::encode($payload, getenv("secret"), 'HS256');
        return $jwt;
    }
}


?>