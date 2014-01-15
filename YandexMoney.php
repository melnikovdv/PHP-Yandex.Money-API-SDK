<?php

namespace Yandex;

use Yandex\YandexMoney\ApiRequestor;
use Yandex\YandexMoney\Operation\OperationDetail;
use Yandex\YandexMoney\Exception as Exceptions;
use Yandex\YandexMoney\Response as Responses;

/**
 * 
 */
class YandexMoney 
{
    /**
     * 
     */
    const VERSION = '1.2.1';

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $logFile;

    /**
     * 
     */
    const URI_API = 'https://money.yandex.ru/api';

    /**
     * 
     */
    const URI_AUTH = 'https://sp-money.yandex.ru/oauth/authorize';

    /**
     * 
     */
    const URI_TOKEN = 'https://sp-money.yandex.ru/oauth/token';

    /**
     * @param string $clientId
     * @param string $logFile
     */
    public function __construct($clientId, $logFile = null)
    {
        self::_validateClientId($clientId);
        $this->clientId = $clientId;
        $this->logFile = $logFile;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     * @param string $redirectUri
     * @param string $scope=
     */
    public static function authorizeUri($clientId, $redirectUri, $scope = null)
    {
        self::_validateClientId($clientId);

        if (!isset($scope) || $scope === '') {
            $scope = 'account-info operation-history';
        }
        $scope = trim(strtolower($scope));

        $res = self::URI_AUTH . "?client_id=$clientId&response_type=code&scope=" .
                urlencode($scope) . "&redirect_uri=" . urlencode($redirectUri);

        return $res;
    }

    /**
     * @param string $code
     * @param string $redirectUri
     * @param string $clientSecret
     * @return \Yandex\YandexMoney\Response\ReceiveTokenResponse
     */
    public function receiveOAuthToken($code, $redirectUri, $clientSecret = null)
    {
        $paramArray['grant_type'] = 'authorization_code';
        $paramArray['client_id'] = $this->clientId;
        $paramArray['code'] = $code;
        $paramArray['redirect_uri'] = $redirectUri;
        if ($client_secret) {
            $paramArray['client_secret'] = $clientSecret;
        }
        $params = http_build_query($paramArray);

        $requestor = new ApiRequestor();
        $resp = $requestor->request(self::URI_TOKEN, $params);

        return new Responses\ReceiveTokenResponse($resp);
    }

    /**
     * @param string $accessToken
     * @return boolean
     */
    public function revokeOAuthToken($accessToken)
    {
        $requestor = new ApiRequestor($accessToken, $this->logFile);
        $resp = $requestor->request(self::URI_API . '/revoke');

        return true;
    }

    /**
     * @param string $accessToken
     * @return \Yandex\YandexMoney\Response\AccountInfoResponse
     */
    public function accountInfo($accessToken)
    {
        $requestor = new ApiRequestor($accessToken, $this->logFile);
        $resp = $requestor->request(self::URI_API . '/account-info');

        return new Responses\AccountInfoResponse($resp);
    }

    /**
     * @param string $accessToken
     * @param int $startRecord
     * @param int $records
     * @param string $type
     * @return \Yandex\YandexMoney\Response\OperationHistoryResponse
     */
    public function operationHistory($accessToken, $startRecord = null, $records = null, $type = null, $from = null, $till = null, $label = null)
    {
        $paramArray = array();
        if (isset($type)) {
            $paramArray['type'] = $type;
        }
        if (isset($startRecord)) {
            $paramArray['start_record'] = $startRecord;
        }
        if (isset($records)) {
            $paramArray['records'] = $records;
        }

        if (isset($label)) {
            $paramArray['label'] = $label;
        }

        if (isset($from)) {
            $paramArray['from'] = $from;
        }

        if (isset($till)) {
            $paramArray['till'] = $till;
        }

        if (count($paramArray) > 0) {
            $params = http_build_query($paramArray);
        } else {
            $params = '';
        }

        $requestor = new ApiRequestor($accessToken, $this->logFile);
        $resp = $requestor->request(self::URI_API . '/operation-history', $params);

        return new Responses\OperationHistoryResponse($resp);
    }

    /**
     * @param string $accessToken
     * @param string $operationId
     */
    public function operationDetail($accessToken, $operationId)
    {
        $paramArray['operation_id'] = $operationId;
        $params = http_build_query($paramArray);

        $requestor = new ApiRequestor($accessToken, $this->logFile);
        $resp = $requestor->request(self::URI_API . '/operation-details', $params);

        return new OperationDetail($resp);
    }

    /**
     * @param string $accessToken
     * @param string $to
     * @param float $amount
     * @param string $comment
     * @param string $message
     * @return \Yandex\YandexMoney\Response\RequestPaymentResponse
     */
    public function requestPaymentP2P($accessToken, $to, $amount, $comment = null, $message = null, $label = null)
    {
        $paramArray['pattern_id'] = 'p2p';
        $paramArray['to'] = $to;
        $paramArray['amount'] = $amount;
        $paramArray['comment'] = $comment;
        $paramArray['message'] = $message;
        $paramArray['label'] = $label;
        $params = http_build_query($paramArray);

        $requestor = new ApiRequestor($accessToken, $this->logFile);
        $resp = $requestor->request(self::URI_API . '/request-payment', $params);

        return new Responses\RequestPaymentResponse($resp);
    }

    /**
     * @param string $accessToken
     * @param string $requestId
     * @return \Yandex\YandexMoney\Response\ProcessPaymentResponse
     */
    public function processPaymentByWallet($accessToken, $requestId) 
    {
        $paramArray['request_id'] = $requestId;
        $paramArray['money_source'] = 'wallet';
        $params = http_build_query($paramArray);

        $requestor = new ApiRequestor($accessToken, $this->logFile);
        $resp = $requestor->request(self::URI_API . '/process-payment', $params);

        return new Responses\ProcessPaymentResponse($resp);
    }

    /**
     * @param string $accessToken
     * @param string $shopParams
     * @return \Yandex\YandexMoney\Response\RequestPaymentResponse
     */
    public function requestPaymentShop($accessToken, $shopParams)
    {
        $params = http_build_query($shopParams);

        $requestor = new ApiRequestor($accessToken, $this->logFile);
        $resp = $requestor->request(self::URI_API . '/request-payment', $params);

        return new Response\RequestPaymentResponse($resp);
    }


    /**
     * @param string $accessToken
     * @param string $requestId
     * @param string $csc
     * @return \Yandex\YandexMoney\Response\ProcessPaymentResponse
     */
    public function processPaymentByCard($accessToken, $requestId, $csc)
    {
        $paramArray['request_id'] = $requestId;
        $paramArray['money_source'] = 'card';
        $paramArray['csc'] = $csc;
        $params = http_build_query($paramArray);

        $requestor = new ApiRequestor($accessToken, $this->logFile);
        $resp = $requestor->request(self::URI_API . '/process-payment', $params);
        
        return new Responses\ProcessPaymentResponse($resp);
    }

    /**
     * @param string $clientId
     * @throws \Yandex\YandexMoney\Exception\Exception
     */
    private static function _validateClientId($clientId)
    {
        if (($clientId == null) || ($clientId === '')) {
            throw new Exceptions\Exception('You must pass a valid application client_id');
        }
    }
}
