<?php
namespace trip69\Pusher;

class utils
{
    static function key_from_url($url)
    {
        $bits=explode('/',$url);
        if(count($bits)==0)
            return null;
        $bit=array_pop($bits);
        $bits=explode('?',$bit);
        if(count($bits)==0)
            return null;
        if(strlen($bits[0])==20 && ctype_xdigit($bits[0]))
            return $bits[0];
        else
            return null;
    }
}

class Client
{
    const data_wait_timeout=10;//seconds

    const event_subscription_succeeded='pusher_internal:subscription_succeeded';

    protected $ws=null;

    protected $url=null;
    protected $key=null;
    protected $site=null;

    //wss://ws.pusherapp.com/app/de504dc5763aeef9ff52?protocol=7&client=js&version=2.1.6&flash=false
    public function __construct($site,$url,$key=null)
    {
        $this->url = $url;
        if($key==null)
            $key=utils::key_from_url($url);
        $this->key=$key;
        $this->site=$site;
        $options=array('blocking'=>false);
        $this->ws = new \WebSocket\Client($this->url,$options);
    }

    public function __destruct()
    {
        if ($this->ws->isConnected())
            $this->ws->close();
        $this->ws = null;
    }

    protected function connect()
    {
        try {
            $this->ws->connect();
            //echo "connected".PHP_EOL;
        } catch (\Exception $ex) {
            echo 'Error connecting to '.$this->site.PHP_EOL;
            return false;
        }
        return true;
    }

    protected function disconnect()
    {
        try {
            $this->ws->close();
            //echo "disconnected".PHP_EOL;
        } catch (\Exception $ex) {
            echo 'Error disconnecting from '.$this->site.PHP_EOL;
            return false;
        }
        return true;
    }

    protected function subscribe($channel)
    {
        $data=array('event'=>'pusher:subscribe','data'=>array('channel'=>$channel));
        $data=json_encode($data);
        $this->ws->send($data);
        return $this->wait_for_event($this::event_subscription_succeeded);
    }

    protected function unsubscribe($channel)
    {
        $data=array('event'=>'pusher:unsubscribe','data'=>array('channel'=>$channel));
        $data=json_encode($data);
        $this->ws->send($data);
    }

    protected function wait_for_event($data)
    {
        $timeout=time()+$this::data_wait_timeout;
        do {
            $receive = $this->ws->receive();
            if ($receive!==null)
            {
                $receive = json_decode($receive);
                if(isset($receive->event) && $receive->event == $data)
                    return true;
                //todo error handling
            }

        } while (time() < $timeout);
        return false;
    }

    protected function listen($duration)
    {
        //echo "listening\r\n";
        $end=time()+$duration;
        while(time() < $end)
        {
            $data=$this->ws->receive();
            if($data!==null)
                echo vardump(json_decode($data)).PHP_EOL;
            sleep(1);
        }
    }
}
?>