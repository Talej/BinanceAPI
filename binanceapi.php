<?php

    require_once('curl.php');

    define('BINANCE_API_HOST', 'https://www.binance.com');

    class BinanceAPI {
        protected $curl;
        protected $errors = array();
        private $key;
        private $secret;

        public function __construct($key, $secret) {
            $this->curl = new cURL();
            $this->curl->options['SSL_VERIFYPEER'] = FALSE;
            $this->curl->options['SSL_VERIFYHOST'] = FALSE;
            $this->curl->headers['Content-Type'] = 'application/json';

            $this->key = $key;
            $this->secret = $secret;
        }

        /**
         *
         * raiseError() - Internal method for logging errors when they occur 
         *
         **/
        protected function raiseError($str) {
            $this->errors[] = $str;
        }

        /**
         *
         * resetErrors() - Internal method for clering errors once they have been retrieved 
         *
         **/
        protected function resetErrors() {
            $this->errors = array();
        }

        /**
         * getErrors() - Method for retrieving any errors that have occurred. After getErrors has been called the errors will be cleared
         *
         * @return array an error containing any errors since the last call of getErrors()
         */
        public function getErrors() {
            $tmp = $this->errors;
            $this->resetErrors();
            return $tmp;
        }

        /**
         *
         * setAuth() - internal method to set header authentication data for cURL
         *
         **/
        protected function setAuth() {
            $this->curl->headers['X-MBX-APIKEY'] = $this->key;
        }

        /**
         *
         * clearAuth() - internal method used to clear auth header after API method has been called
         *
         **/
        protected function clearAuth() {
            if (isset($this->curl->headers['X-MBX-APIKEY'])) unset($this->curl->headers['X-MBX-APIKEY']);
        }

        /**
         *
         * sign() - Internal method for creating a signature for API methods that require it
         *
         **/
        protected function sign(&$args) {
            $args['timestamp'] = round(microtime(TRUE)*1000);
            $str = http_build_query($args);
            $signature = hash_hmac('SHA256', $str, $this->secret);

            $args['signature'] = $signature;
        }

        /**
         *
         * mkURL() - Internal method used to create full URL for an endpoint
         *
         **/ 
        protected function mkURL($path) {
            return BINANCE_API_HOST . $path;
        }

        /**
         *
         * getBody() - internal method for correctly handling responses received from the API
         *
         **/
        protected function getBody($result) {
            if ($result->headers['Status-Code'] == '200') {
                if (($body = json_decode($result->body)) !== NULL) {
                    return $body;
                } else {
                    return $result->body;
                }
            } else if ($result->headers['Status-Code'] == '504') {
                return '504 Status UNKNOWN';
            } else {
                if (($body = json_decode($result->body)) !== NULL) {
                    if (isset($body->msg) && isset($body->code)) {
                        $this->raiseError($body->code . ': ' . $body->msg);
                    } else {
                        $this->raiseError($result->headers['Status']);
                    }
                } else {
                    $this->raiseError($result->headers['Status']);
                }
            }

            return FALSE;
        }

        /**
         *
         * sendRequest() - internal method used to send a get, post, put or delete cURL request to an API endpoint
         *
         **/
        protected function sendRequest($method, $uri, $args=array()) {
            if (method_exists($this->curl, $method)) {
                if ($result = $this->curl->{$method}($this->mkURL($uri), $args)) {
                    $this->clearAuth();
                    return $this->getBody($result);
                }

                $this->raiseError($this->curl->error());
            } else {
                $this->raiseError($method . ' is not a valid HTTP method');
            }
            $this->clearAuth();

            return FALSE;
        }

        /***
         *
         * public api methods
         *
         ***/

        /**
         *
         * ping() - wrapper for API method to test connectivity
         *
         * @return bool true on success, false on failure. Use getErrors() to retrieve errors on failure
         **/
        public function ping() {
            return ($this->sendRequest('get', '/api/v1/ping') ? TRUE : FALSE);
        }

        /**
         *
         * time() - wrapper for API method to test connectivity and get the current server time
         *
         * @return bool timestamp on success, false on failure. Use getErrors() to retrieve errors on failure
         **/
        public function time() {
            if (($result = $this->sendRequest('get', '/api/v1/time')) && isset($result->serverTime)) {
                return $result->serverTime;
            }

            return FALSE;
        }

        /**
         *
         * depth() - get the order book for a specific ticker symbol
         *
         * @return mixed object containing order book results on success, false on failure. use getErrors() to retrieve errors on failure
         *
         * @param string $symbol the ticker symbol to retrieve
         * @param int $limit maximum number of results, defaults to 100. Optional
         **/
        public function depth($symbol, $limit=100) {
            $args = array(
                'symbol' => $symbol,
                'limit'  => $limit
            );

            return $this->sendRequest('get', '/api/v1/depth', $args);
        }

        /**
         *
         * validate_aggTrades() - Internal method for validating aggTrades arguments
         *
         **/
        protected function validate_aggTrades($args) {
            if (isset($args['startTime']) && isset($args['endTime']) && $args['endTime']-$args['startTime'] > 86400000) {
                $this->raiseError('endTime must be no more than 24 hours after startTime');
                return FALSE;
            }

            return TRUE;
        }

        /**
         *
         * aggTrades() - Get compressed, aggregate trades. Trades that fill at the time, from the same order, with the same price will have the quantity aggregated.
         *
         * @return mixed object containing results on success, false on failure. use getErrors() to retrieve errors on failure
         *
         * @param string $symbol the ticker symbol to retrieve
         * @param int $fromId ID to get aggregate trades from INCLUSIVE. Optional
         * @param int $startTime Timestamp in ms to get aggregate trades from INCLUSIVE. Optional
         * @param int $endTime Timestamp in ms to get aggregate trades until INCLUSIVE. Optional
         * @param int $limit maximum number of results, defaults to 500. Optional
         **/
        public function aggTrades($symbol, $fromId=NULL, $startTime=NULL, $endTime=NULL, $limit=500) {
            $args = array('symbol' => $symbol);
            if ($fromId) $args['fromId'] = $fromId;
            if ($startTime) $args['startTime'] = $startTime;
            if ($endTime) $args['endTime'] = $endTime;
            if (!$startTime || !$endTime) $args['limit'] = $limit;

            if (!$this->validate_aggTrades($args)) return FALSE;

            return $this->sendRequest('get', '/api/v1/aggTrades', $args);
        }

        /**
         *
         * validate_candlesticks() - Internal method for validating candlesticks arguments
         *
         **/
        protected function validate_candlesticks($args) {
            return TRUE;
        }

        /**
         *
         * candlesticks() - Get candlestick data based on a specified interval
         *
         * @return mixed object containing results on success, false on failure. use getErrors() to retrieve errors on failure
         *
         * @param string $symbol the ticker symbol to retrieve
         * @param enum $interval Time period for the candlesticks. Options available are 1m 3m 5m 15m 30m 1h 2h 4h 6h 8h 12h 1d 3d 1w 1M
         * @param int $limit maximum number of results, defaults to 500. Optional
         * @param int $startTime Timestamp in ms to get candlesticks from INCLUSIVE. Optional
         * @param int $endTime Timestamp in ms to get candlesticks until INCLUSIVE. Optional
         **/
        public function candlesticks($symbol, $interval, $limit=NULL, $startTime=NULL, $endTime=NULL) {
            $args = array('symbol' => $symbol, 'interval' => $interval);
            if ($startTime) $args['startTime'] = $startTime;
            if ($endTime) $args['endTime'] = $endTime;
            if (!$startTime || !$endTime) $args['limit'] = $limit;

            if (!$this->validate_candlesticks($args)) return FALSE;

            return $this->sendRequest('get', '/api/v1/klines', $args);
        }
        
        /**
         *
         * ticker24hr() - Get 24 hour price change statistics for a specified ticker
         *
         * @return mixed object containing results on success, false on failure. use getErrors() to retrieve errors on failure
         *
         * @param string $symbol the ticker symbol to retrieve
         **/
        public function ticker24hr($symbol) {
            $args = array(
                'symbol' => $symbol
            );

            return $this->sendRequest('get', '/api/v1/ticker/24hr', $args);
        }

        /**
         *
         * allPrices() - Get the latest prices for all ticker symbols
         *
         * @return mixed object containing results on success, false on failure. use getErrors() to retrieve errors on failure
         **/
        public function allPrices() {
            return $this->sendRequest('get', '/api/v1/ticker/allPrices');
        }

        /**
         *
         * getPrice() - Get the latest price for a specific symbol
         *
         *
         * @return mixed the latest price on success or FALSE on failure
         *
         * @param string $symbol the symbol to retrieve price for
         **/
         public function getPrice($symbol) {
             if ($prices = $this->allPrices()) {
                 foreach ($prices as $price) {
                     if (strtoupper($price->symbol) == strtoupper($symbol)) {
                         return $price->price;
                     }
                 }
             }

             $this->raiseError('Unable to find price for ' . $symbol);
             return FALSE;
         }

        /**
         *
         * allBookTickers() - Get the best price/quantity for all ticker symbols
         *
         * @return mixed object containing results on success, false on failure. use getErrors() to retrieve errors on failure
         **/
        public function allBookTickers() {
            return $this->sendRequest('get', '/api/v1/ticker/allBookTickers');
        }

        /***
         *
         * account api methods
         *
         ***/

        /**
         *
         * validate_order() - Internal method for validating order arguments
         *
         **/
        protected function validate_order($args) {
            return TRUE;
        }

        /**
         *
         * order() - Place a new order based on the details provided
         *
         * @return mixed object containing results on success, false on failure. use getErrors() to retrieve errors on failure
         *
         * @param string $symbol the ticker symbol to place the order for
         * @param string $side BUY or SELL order
         * @param string $type LIMIT or MARKET
         * @param string $timeInForce GTC or IOC
         * @param float $quantity quantity to buy or sell
         * @param float $price price for the order
         * @param string $newClientOrderId A unique id for the order. Automatically generated if not sent.
         * @param float $stopPrice stop price used for stop orders
         * @param float $icebergQty Used for iceberg orders
         **/
        public function order($symbol, $side, $type, $timeInForce, $quantity, $price, $newClientOrderId=NULL, $stopPrice=NULL, $icebergQty=NULL) {
            $args = array(
                'symbol'      => $symbol,
                'side'        => $side,
                'type'        => $type,
                'timeInForce' => $timeInForce,
                'quantity'    => $quantity,
                'price'       => $price,
            );

            if ($newClientOrderId) $args['newClientOrderId'] = $newClientOrderId;
            if ($stopPrice) $args['stopPrice'] = $stopPrice;
            if ($icebergQty) $args['icebergQty'] = $icebergQty;

            if (!$this->validate_order($args)) return FALSE;

            $this->sign($args);
            $this->setAuth();

            if ($result = $this->sendRequest('post', '/api/v3/order', $args)) {
                return $result;
            }

            return FALSE;
        }

        /**
         *
         * orderTest() - Test placing a new order based on the details provided
         *
         * @return mixed object containing results on success, false on failure. use getErrors() to retrieve errors on failure
         *
         * @param string $symbol the ticker symbol to place the order for
         * @param string $side BUY or SELL order
         * @param string $type LIMIT or MARKET
         * @param string $timeInForce GTC or IOC
         * @param float $quantity quantity to buy or sell
         * @param float $price price for the order
         * @param string $newClientOrderId A unique id for the order. Automatically generated if not sent.
         * @param float $stopPrice stop price used for stop orders
         * @param float $icebergQty Used for iceberg orders
         **/
        public function orderTest($symbol, $side, $type, $timeInForce, $quantity, $price, $newClientOrderId=NULL, $stopPrice=NULL, $icebergQty=NULL, $recvWindow=5000) {
            $args = array(
                'symbol'      => $symbol,
                'side'        => $side,
                'type'        => $type,
                'timeInForce' => $timeInForce,
                'quantity'    => $quantity,
                'price'       => $price,
            );

            if ($newClientOrderId) $args['newClientOrderId'] = $newClientOrderId;
            if ($stopPrice) $args['stopPrice'] = $stopPrice;
            if ($icebergQty) $args['icebergQty'] = $icebergQty;
            if ($recvWindow) $args['recvWindow'] = $recvWindow;

            if (!$this->validate_order($args)) return FALSE;

            $this->sign($args);
            $this->setAuth();

            if ($result = $this->sendRequest('post', '/api/v3/order/test', $args)) {
                return $result;
            }

            return FALSE;
        }

        /**
         *
         * validate_queryOrder() - Internal method for validating queryOrder arguments
         *
         **/
        public function validate_queryOrder($args) {
            if (!isset($args['orderId']) && !isset($args['origClientOrderId'])) {
                $this->raiseError('orderId or origClientOrderId must be provided');
                return FALSE;
            }

            return TRUE;
        }

        /**
         *
         * queryOrder() - Query an order based on the details provided
         *
         * @return mixed object containing results on success, false on failure. use getErrors() to retrieve errors on failure
         *
         * @param string $symbol the ticker symbol to query the order for
         * @param int $orderId id of the order to query
         * @param string $origClientOrderId client order Id to query
         * @param int $recvWindow specific the number of milliseconds after timestamp the request is valid for. If recvWindow is not sent, it defaults to 5000 millisecond
         **/
        public function queryOrder($symbol, $orderId=NULL, $origClientOrderId=NULL, $recvWindow=5000) {
            $args = array('symbol' => $symbol);
            if ($orderId) $args['orderId'] = $orderId;
            if ($origClientOrderId) $args['origClientOrderId'] = $origClientOrderId;
            if ($recvWindow) $args['recvWindow'] = $recvWindow;

            if (!$this->validate_queryOrder($args)) return FALSE;

            $this->sign($args);
            $this->setAuth();

            if ($result = $this->sendRequest('get', '/api/v3/order', $args)) {
                return $result;
            }

            return FALSE;
        }

        /**
         *
         * cancelOrder() - Cancel an order based on the details provided
         *
         * @return mixed object containing results on success, false on failure. use getErrors() to retrieve errors on failure
         *
         * @param string $symbol the ticker symbol to cancel the order for
         * @param int $orderId id of the order to cancel
         * @param string $origClientOrderId client order Id to cancel
         * @param string $newClientOrderId Used to uniquely identify this cancel. Automatically generated by default.
         * @param int $recvWindow specific the number of milliseconds after timestamp the request is valid for. If recvWindow is not sent, it defaults to 5000 millisecond
         **/
        public function cancelOrder($symbol, $orderId=NULL, $origClientOrderId=NULL, $newClientOrderId=NULL, $recvWindow=5000) {
            $args = array('symbol' => $symbol);
            if ($orderId) $args['orderId'] = $orderId;
            if ($origClientOrderId) $args['origClientOrderId'] = $origClientOrderId;
            if ($newClientOrderId) $args['newClientOrderId'] = $newClientOrderId;
            if ($recvWindow) $args['recvWindow'] = $recvWindow;

            if (!$this->validate_queryOrder($args)) return FALSE;

            $this->sign($args);
            $this->setAuth();

            if ($result = $this->sendRequest('delete', '/api/v3/order', $args)) {
                return $result;
            }

            return FALSE;
        }

        /**
         *
         * openOrders() - retrieve all open orders
         *
         * @return mixed object containing results on success, false on failure. use getErrors() to retrieve errors on failure
         *
         * @param string $symbol the ticker symbol to query the orders for
         * @param int $recvWindow specific the number of milliseconds after timestamp the request is valid for. If recvWindow is not sent, it defaults to 5000 millisecond
         **/
        public function openOrders($symbol, $recvWindow=5000) {
            $args = array('symbol' => $symbol);
            if ($recvWindow) $args['recvWindow'] = $recvWindow;

            $this->sign($args);
            $this->setAuth();

            if ($result = $this->sendRequest('get', '/api/v3/openOrders', $args)) {
                return $result;
            }

            return FALSE;
        }

        /**
         *
         * allOrders() - retrieve all orders
         *
         * @return mixed object containing results on success, false on failure. use getErrors() to retrieve errors on failure
         *
         * @param string $symbol the ticker symbol to query the orders for
         * @param int $limit maximum number of results, defaults to 500. Optional
         * @param int $recvWindow specific the number of milliseconds after timestamp the request is valid for. If recvWindow is not sent, it defaults to 5000 millisecond
         **/
        public function allOrders($symbol, $orderId=NULL, $limit=500, $recvWindow=5000) {
            $args = array('symbol' => $symbol);
            if ($orderId) $args['orderId'] = $orderId;
            if ($limit) $args['limit'] = $limit;
            if ($recvWindow) $args['recvWindow'] = $recvWindow;

            $this->sign($args);
            $this->setAuth();

            if ($result = $this->sendRequest('get', '/api/v3/allOrders', $args)) {
                return $result;
            }

            return FALSE;
        }

        /**
         *
         * accountInfo() - retrieve all account information
         *
         * @return mixed object containing results on success, false on failure. use getErrors() to retrieve errors on failure
         *
         * @param int $recvWindow specific the number of milliseconds after timestamp the request is valid for. If recvWindow is not sent, it defaults to 5000 millisecond
         **/
        public function accountInfo($recvWindow=5000) {
            $args = array();
            if ($recvWindow) $args['recvWindow'] = $recvWindow;

            $this->sign($args);
            $this->setAuth();

            if ($result = $this->sendRequest('get', '/api/v3/account', $args)) {
                return $result;
            }

            return FALSE;
        }

        /**
         *
         * myTrades() - retrieve all trades for a specific symbol
         *
         * @return mixed object containing results on success, false on failure. use getErrors() to retrieve errors on failure
         *
         * @param string $symbol the ticker symbol to query the orders for
         * @param int $limit maximum number of results, defaults to 500. Optional
         * @param int $fromId TradeId to fetch from. Default gets most recent trades.
         * @param int $recvWindow specific the number of milliseconds after timestamp the request is valid for. If recvWindow is not sent, it defaults to 5000 millisecond
         **/
        public function myTrades($symbol, $limit=500, $fromId=NULL, $recvWindow=5000) {
            $args = array('symbol' => $symbol);
            if ($fromId) $args['fromId'] = $fromId;
            if ($limit) $args['limit'] = $limit;
            if ($recvWindow) $args['recvWindow'] = $recvWindow;

            $this->sign($args);
            $this->setAuth();

            if ($result = $this->sendRequest('get', '/api/v3/myTrades', $args)) {
                return $result;
            }

            return FALSE;
        }

        /**
         *
         * User stream endpoints
         *
         **/

         public function startUserDataStream() {
             $this->setAuth();

             if ($result = $this->sendRequest('post', '/api/v1/userDataStream')) {
                 return $result;
             }

             return FALSE;
         }

         public function keepaliveUserDataStream($listenKey) {
             $args = array('listenKey' => $listenKey);

             $this->setAuth();

             if ($result = $this->sendRequest('put', '/api/v1/userDataStream', $args)) {
                 return $result; 
             }

             return FALSE;
         }

         public function closeUserDataStream($listenKey) {
             $args = array('listenKey' => $listenKey);

             $this->setAuth();

             if ($result = $this->sendRequest('delete', '/api/v1/userDataStream', $args)) {
                 return $result;
             }

             return FALSE;
         }
    }

