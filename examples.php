<!DOCTYPE html>
<html>
<head>
    <title>Binance API Examples</title>
    <style type="text/css">

        th, td {
            text-align:left;
            vertical-align:top;
            border:1px solid #000000;
            padding:5px 10px;
        }

        div {
            max-width:800px;
            white-space: pre-wrap; /* css-3 */
            white-space: -moz-pre-wrap; /* Mozilla, since 1999 */
            white-space: -pre-wrap; /* Opera 4-6 */
            white-space: -o-pre-wrap; /* Opera 7 */
            word-wrap: break-word; /* Internet Explorer 5.5+ */
        }

    </style>
</head>
<body>
    <table cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
        <th>Method</th>
        <th>Result</th>
        <th>Errors</th>
    </tr>
<?php

    // Load the Binance API library
    require_once('binanceapi.php');

    // Create the Binance API object by passing your API key & secret
    $api = new BinanceAPI('<APIKey>', '<APISecret>');


    // EXAMPLE - Get latest price of a symbol
    $result = $api->getPrice('LTCBTC');
    dump('getPrice', $result, $api->getErrors());

    // EXAMPLE - Get the depth of a symbol
    $result = $api->depth('LTCBTC', 5);
    dump('depth', $result, $api->getErrors());

    // EXAMPLE - Place a LIMIT order
    $result = $api->order('LTCBTC', 'BUY', 'LIMIT', 'GTC', 10, 0.1);
    dump('order', $result, $api->getErrors());

    // EXAMPLE - Place a MARKET order
    $result = $api->order('LTCBTC', 'BUY', 'MARKET', NULL, 10, NULL);
    dump('order', $result, $api->getErrors());

    // EXAMPLE - Get order status
    if ($result = $api->queryOrder('LTCBTC', 1234)) {
        print 'Order status is ' . $result->status . '<br>';
    } 
    dump('queryOrder', $result, $api->getErrors());

    // EXAMPLE - cancel an order
    $result = $api->cancelOrder('LTCBTC', 1234);
    dump('cancelOrder', $result, $api->getErrors());

    // EXAMPLE - Get a list of open orders 
    $result = $api->openOrders('LTCBTC');
    dump('openOrders', $result, $api->getErrors());

    // EXAMPLE - Get all current positions
    // An example of a successful accountInfo call
    if ($result = $api->accountInfo()) {
        dump('balances', $result->balances, $api->getErrors());
    }



    /**
     *
     * General examples of most API methods below including successful and failed examples
     *
     **/

/*
    // Call ping method
    $result = $api->ping();
    dump('ping', $result, $api->getErrors());

    // Call time method
    $result = $api->time();
    dump('time', $result, $api->getErrors());
    
    // An example of a failed depth method call
    $result = $api->depth('BADSYMBOL');
    dump('depth', $result, $api->getErrors());

    // An example of a successful depth method call
    $result = $api->depth('LTCBTC');
    dump('depth', $result, $api->getErrors());

    // An example of a failed aggTrades call
    $result = $api->aggTrades('BADSYMBOL');
    dump('aggTrades', $result, $api->getErrors());

    // An example of a successful aggTrades call
    $result = $api->aggTrades('LTCBTC');
    dump('aggTrades', $result, $api->getErrors());

    // An example of a successul candlesticks call
    $result = $api->candlesticks('LTCBTC', '1h');
    dump('candlesticks', $result, $api->getErrors());

    // An example of a failed candlesticks call
    $result = $api->candlesticks('BADSYMBOL', '1h');
    dump('candlesticks', $result, $api->getErrors());

    // An example of a failed candlesticks call
    $result = $api->candlesticks('LTCBTC', 'BADINTERVAL');
    dump('candlesticks', $result, $api->getErrors());

    // An example of a failed ticker24hr call
    $result = $api->ticker24hr('BADTICKER');
    dump('ticker24hr', $result, $api->getErrors());

    // An example of a successul ticker24hr call
    $result = $api->ticker24hr('LTCBTC');
    dump('ticker24hr', $result, $api->getErrors());

    // An example of a successul allPrices call
    $result = $api->allPrices();
    dump('allPrices', $result, $api->getErrors());

    // An example of a successul allBookTickers call
    $result = $api->allBookTickers();
    dump('allBookTickers', $result, $api->getErrors());

    // An example of a successul order call
    // WARNING ---- Be careful with this if you don't want to actual place an order!!!!!!!!!!!
    $result = $api->order('LTCBTC', 'BUY', 'LIMIT', 'GTC', 10, 0.101);
    dump('order', $result, $api->getErrors());

    // An example of a successul orderTest call
    $result = $api->orderTest('LTCBTC', 'BUY', 'MARKET', 'GTC', 1, 0.1);
    dump('orderTest', $result, $api->getErrors());

    // An example of a failed queryOrder call
    $result = $api->queryOrder('LTCBTC', 1234); // bad orderId
    dump('queryOrder', $result, $api->getErrors());

    // An example of a successful queryOrder call
    $result = $api->queryOrder('LTCBTC', NULL, 'MYORDER');
    dump('queryOrder', $result, $api->getErrors());

    // An example of a successful cancelOrder call
    $result = $api->cancelOrder('LTCBTC', NULL, 'MYORDER');
    dump('cancelOrder', $result, $api->getErrors());

    // An example of a successful openOrders call
    $result = $api->openOrders('LTCBTC');
    dump('openOrders', $result, $api->getErrors());

    // An example of a successful allOrders call
    $result = $api->allOrders('LTCBTC');
    dump('allOrders', $result, $api->getErrors());

    // An example of a successful accountInfo call
    $result = $api->accountInfo();
    dump('accountInfo', $result, $api->getErrors());

    // An example of a successful myTrades call
    $result = $api->myTrades('LTCBTC');
    dump('myTrades', $result, $api->getErrors());
*/

    // function for display of example call results
    function dump($method, $result, $errors) {
        $str = '<tr>'
             . '<td>' . htmlentities($method) . '</td>'
             . '<td><div style="color:' . ($result ? 'blue' : 'red') . '">' . htmlentities(json_encode($result)) . '</div></td>'
             . '<td style="color:red">' . (!empty($errors) ? implode('<br>', $errors) : '') . '</td>'
             . '</tr>';

        print $str;
    }

?>
    </table>
</body>
</html>
