<?php
require_once './JWT/JWToken.php';

class Bt_Api
{
    private static $ch = NULL;

    private static $api_url = 'https://api.btc-exchange.com/';
    private static $api_key = '';
    private static $priv_key_file = '';
    private static $priv_key = '';

    function __construct()
    {
    }

    public function setApiKey($api_key)
    {
        self::$api_key = $api_key;
    }

    public function setPrivKeyFile($priv_key_file)
    {
        self::$priv_key_file = $priv_key_file;
        self::$priv_key = base64_encode(file_get_contents(self::$priv_key_file));
    }


    public function getMe()
    {
        return  $this->execute('papi/web/members/me', [], 'get');
    }

    private function generateJwtToken()
    {
        $str = 'QWERTYUIOPASDFGHJKLZXCVBNM1234567890';
        $shuffled = substr(str_shuffle($str), 1, 12);

        return JWToken::encode([
            'iat' => time(),
            'exp' => time()+30,
            'sub' => 'api_key_jwt',
            'iss' => 'external',
            'jti' => $shuffled
        ], base64_decode(self::$priv_key), 'RS256');
    }

    private function getToken()
    {

        $post_data = http_build_query([
            'kid' => self::$api_key,
            'jwt_token' => $this->generateJwtToken()
        ], '', '&');

        // any extra headers
        $headers = ['x-api-key' => self::$api_key ];

        // our curl handle (initialize if required)
        if (is_null(self::$ch))
        {
            self::$ch = curl_init();
            curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(self::$ch, CURLOPT_USERAGENT,
                'Mozilla/4.0 (compatible; PHP Client; ' . php_uname('s') . '; PHP/' .
                phpversion() . ')');
        }

        curl_setopt(self::$ch, CURLOPT_URL, self::$api_url . 'pauth/web/sessions/generate_jwt');
        curl_setopt(self::$ch, CURLOPT_POST, 1);
        curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $post_data);

        curl_setopt(self::$ch, CURLOPT_HTTPHEADER, $headers);

        // run the query
        $res = curl_exec(self::$ch);

        if ($res === false){
            return $this->error('Could not get reply: ('.self::$api_url . 'pauth/web/sessions/generate_jwt) ' . curl_error(self::$ch));
        }

        $dec = json_decode($res, true);

        if (empty($dec['token']))
            return $this->error('Invalid data received, please make sure connection is working and requested API exists: ' . $res);

        return $this->success($dec['token']);
    }


    private function execute($path, $req, $method = 'post')
    {

        $post_data = http_build_query($req, '', '&');
        $aToken = $this->getToken();

        // any extra headers
        $headers = [
            'x-api-key' => self::$api_key,
            'Authorization: Bearer ' . ($aToken['status'] ? $aToken['data'] : ''),
        ];

        // our curl handle (initialize if required)
        if (is_null(self::$ch)) {
            self::$ch = curl_init();
        } else {
            curl_reset(self::$ch);
        }

        curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(self::$ch, CURLOPT_USERAGENT,
            'Mozilla/4.0 (compatible; PHP Client; ' . php_uname('s') . '; PHP/' .
            phpversion() . ')');


        if ($method == 'post')
        {
            curl_setopt(self::$ch, CURLOPT_URL, self::$api_url . $path);
            curl_setopt(self::$ch, CURLOPT_POST, 1);
            curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $post_data);
        } else {
            curl_setopt(self::$ch, CURLOPT_URL, self::$api_url . $path . '?'.$post_data);
        }

        curl_setopt(self::$ch, CURLOPT_HTTPHEADER, $headers);

        // run the query
        $res = curl_exec(self::$ch);

        return $res;
    }

    private function error($sMessage = '')
    {
        return ['status' => false, 'message' => $sMessage];
    }

    private function success($mData = null)
    {
        return ['status' => true, 'data' => $mData];
    }

}
