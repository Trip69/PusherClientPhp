<?php
namespace Pusher;

class Client
{
    private $url=null;
    private $ws=null;
    private $key=null;

    //wss://ws.pusherapp.com/app/de504dc5763aeef9ff52?protocol=7&client=js&version=2.1.6&flash=false
    public function __construct($site,$url,$key)
    {
        $this->url = $url;
        $options=array('blocking'=>false);
        $this->ws = new \WebSocket\Client($this->url,$options);
    }

    public function __destruct()
    {
        if ($this->ws->isConnected())
            $this->ws->close();
        $this->ws = null;
    }

    public function connect()
    {
        $this->ws->connect();
        echo "connected\r\n";
    }

    public function subscribe($channel)
    {
        $data=array('event'=>'pusher:subscribe','data'=>array('channel'=>$channel));
        $data=json_encode($data);
        $this->ws->send($data);
    }

    public function unsubscribe($channel)
    {
        $data=array('event'=>'pusher:unsubscribe','data'=>array('channel'=>$channel));
        $data=json_encode($data);
        $this->ws->send($data);
    }


    public function listen($duration)
    {
        //echo "listening\r\n";
        $end=time()+$duration;
        while(time() < $end)
        {
            $data=$this->ws->receive();
            if($data!==null)
                echo json_decode($data).PHP_EOL;
            sleep(1);
        }
    }
}

$push_test=new pusher_client('bitstamp','wss://ws.pusherapp.com/app/de504dc5763aeef9ff52?protocol=7&client=js&version=2.1.6&flash=false','de504dc5763aeef9ff52');
$push_test->connect();
//$push_test->subscribe('live_trades');
$push_test->subscribe('live_trades_btcusd');
$push_test->subscribe('live_trades_xrpusd');
$push_test->listen(61);
echo 'Finished';
?>