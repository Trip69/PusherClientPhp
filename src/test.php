<?php
namespace trip69\Pusher;
$push_test=new Client('bitstamp','wss://ws.pusherapp.com/app/de504dc5763aeef9ff52?protocol=7&client=js&version=2.1.6&flash=false','de504dc5763aeef9ff52');
$push_test->connect();
//$push_test->subscribe('live_trades');
$push_test->subscribe('live_trades_btcusd');
$push_test->subscribe('live_trades_xrpusd');
$push_test->listen(61);
echo 'Finished';
?>