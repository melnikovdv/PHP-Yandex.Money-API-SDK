<?php

namespace Yandex\YandexMoney;

/**
 * 
 */
class ApiRequestor 
{
    /**
     * 
     */
    const USER_AGENT = 'yamolib-php';

    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var string
     */
    private $logFile;

    /**
     * @param string $accessToken
     * @param string $logFile
     */
    public function __construct($accessToken = null, $logFile = null)
    {
        $this->accessToken = $accessToken;
        $this->logFile = $logFile;
    }

    /**
     * @param string $uri
     * @param string $params
     * @return array
     */
    public function request($uri, $params = null)
    {
        list($rbody, $rcode) = $this->_curlRequest($uri, $params);

        return $this->_interpretResponse($rbody, $rcode);
    }

    /**
     * @param string $uri
     * @param string $params
     * @return array
     */
    private function _curlRequest($uri, $params)
    {
        $curl = curl_init($uri);

        $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8;';
        if ($this->accessToken) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($curl, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //        curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_TIMEOUT, 80);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        //        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($curl, CURLOPT_CAINFO, dirname(__FILE__) . '/../Resources/data/ca-certificate.crt');
                                    
        $this->_log($this->_makeRequestLogMessage($uri, $params));  

        $rbody = curl_exec($curl);
        
        $errno = curl_errno($curl);
        $error = curl_error($curl);
        $rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);        
        
        $this->_log($this->_makeResponseLogMessage($uri, $params, $rcode, $errno, $error));

        if ($rbody === false) {                
            $this->_handleCurlError($errno, $error);
        }
        
        return array($rbody, $rcode);
    }

    /**
     * @param string $rbody
     * @param int $rcode
     * @return array $response
     * 
     * @throws \Yandex\YandexMoney\Exception\ApiException
     */
    private function _interpretResponse($rbody, $rcode)
    {
        if ($rcode < 200 || $rcode >= 300) {
            $this->_handleApiError($rbody, $rcode, $resp);
        }

        try {
            $resp = json_decode($rbody, true);            
        } catch (Exception $e) {
            throw new Exception\ApiException("Invalid response body from API: $rbody (HTTP response code was $rcode)", $rcode, $rbody);
        }    

        // Only for PHP 5 >= 5.3.0
        // if (json_last_error() !== JSON_ERROR_NONE) {
        //     throw new YM_ApiError("Json parsing error with json_last_error code = " . json_last_error(), $rcode, $rbody);
        // }

        if ($resp === null) {
            throw new Exception\ApiException("Server response body is null: $rbody (HTTP response code was $rcode)", $rcode, $rbody);
        }
        
        return $resp;
    }

    /**
     * @param int $errno
     * @param string $message
     * 
     * @throws \Yandex\YandexMoney\Exception\ApiConnectionException
     */
    private function _handleCurlError($errno, $message)
    {
        switch ($errno) {
            case CURLE_COULDNT_CONNECT:
            case CURLE_COULDNT_RESOLVE_HOST:
            case CURLE_OPERATION_TIMEOUTED:
                $msg = "Could not connect to Yandex.Money. Please check your internet connection and try again.";
                break;
            case CURLE_SSL_CACERT:
            case CURLE_SSL_PEER_CERTIFICATE:
                $msg = "Could not verify Yandex.Money's SSL certificate. Please make sure that your network is not intercepting certificates.";
                break;
            default:
                $msg = "Unexpected error communicating with Yandex.Money.";
        }

        $msg .= "\n\n(Network error: $message)";

        throw new Exception\ApiConnectionException($msg);
    }

    /**
     * @param string $rbody
     * @param string $rcode
     * @param string $resp
     * 
     * @throws \Yandex\YandexMoney\Exception\ApiException
     * @throws \Yandex\YandexMoney\Exception\InvalidTokenException
     * @throws \Yandex\YandexMoney\Exception\InsufficientScopeException
     * @throws \Yandex\YandexMoney\Exception\InternalServerErrorException
     */
    private function _handleApiError($rbody, $rcode, $resp)
    {
        switch ($rcode) {
            case 400:
                throw new Exception\ApiException('Invalid request error', $rcode, $rbody, $resp);
            case 401:
                throw new Exception\InvalidTokenException('Nonexistent, expired, or revoked token specified.', $rcode, $rbody, $resp);
            case 403:
                throw new Exception\InsufficientScopeException('The token does not have permissions for the requested operation.',
                    $rcode, $rbody, $resp);
            case 500:
                throw new Exception\InternalServerErrorException('It is a technical error occurs, the server responds with the HTTP code
                    500 Internal Server Error. The application should repeat the request with the same parameters later.',
                    $rcode, $rbody, $resp);
            default:
                throw new Exception\ApiException('Unknown API response error. You should inform your software developer.',
                    $rcode, $rbody, $resp);
        }
    }

    /**
     * @param string $message
     * 
     * @throws \Yandex\YandexMoney\Exception\Exception
     */
    private function _log($message)
    {
        $f = $this->logFile;
        if ($f !== null) {
            if (!file_exists($f)) {
                throw new Exception\Exception("log file $f not found");
            }
            if (!is_file($f)) {
                throw new Exception\Exception("log file $f is not a file");
            }
            if (!is_writable($f)) {
                throw new Exception\Exception("log file $f is not writable");
            }
                
            if (!$handle = fopen($f, 'a')) {
                throw new Exception\Exception("couldn't open log file $f for appending");
            }
            
            $time = '[' . date("Y-m-d H:i:s") . '] ';
            if (fwrite($handle, $time . $message . "\r\n") === false) {
                throw new Exception\Exception("couldn't fwrite message log to $f"); 
            }
            
            fclose($handle);                                
        }
    }

    /**
     * @param string $uri
     * @param string $params
     * 
     * @return string
     */
    private function _makeRequestLogMessage($uri, $params)
    {
        if ($this->logFile === null) {
            return '';
        }

        $m = "request: " . $uri . '; ';        

        $token = $this->accessToken;
        if (isset($token)) {
            $m = $m . 'token = *' . substr($token, -4) . '; ';
        }

        parse_str($params);        
        if (isset($to)) {
            $m = $m . 'param to = *' . substr($to, -4) . '; ';
        }
        
        if (isset($pattern_id)) {
            $m = $m . 'param pattern_id = ' . $pattern_id . '; ';
        }
        
        if (isset($request_id)) {
            $m = $m . 'param request_id = *' . substr($request_id, -4) . '; ';
        }

        return $m;
    }

    /**
     * @param string $uri
     * @param string $params
     * @param int $code
     * @param int $errno
     * @param string $error
     * 
     * @return string
     */
    private function _makeResponseLogMessage($uri, $params, $code, $errno, $error)
    { 
        if ($this->logFile === null) {
            return '';
        }

        $m = $this->_makeRequestLogMessage($uri, $params);
        $m = str_replace('request: ', 'response: ', $m);
        $m = $m . "http_code = $code; curl_errno = $errno; curl_error = $error";

        return $m;
    }
}
