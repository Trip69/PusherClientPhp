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
    const event_connection_established='pusher:connection_established';

    protected $ws=null;

    protected $url=null;
    protected $key=null;
    protected $site=null;

    protected $pusher_connected=false;
    protected $socket_id=null;
    protected $activity_timeout=null;

    protected $arr_channels=array();
    protected $return_events=array();

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

    public function add_return_events($event)
    {
        $this->return_events[]=$event;
    }

    public function __destruct()
    {
        if ($this->ws->isConnected())
            $this->ws->close();
        $this->ws = null;
    }

    public function connect()
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

    public function disconnect()
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

    public function subscribe($channel)
    {
        $data=array('event'=>'pusher:subscribe','data'=>array('channel'=>$channel));
        $data=json_encode($data);
        $this->ws->send($data);
        return $this->wait_for_event($this::event_subscription_succeeded);
    }

    public function unsubscribe($channel)
    {
        $data=array('event'=>'pusher:unsubscribe','data'=>array('channel'=>$channel));
        $data=json_encode($data);
        $this->ws->send($data);
    }

    public function ping()
    {
        $this->ws->send('ping','ping');
    }

    public function pong()
    {
        $this->ws->send('pong','pong');
    }

    protected function wait_for_event($data)
    {
        $timeout=time()+$this::data_wait_timeout;
        do {
            $receive = $this->ws->receive();
            if ($receive!==null)
            {
                $receive = json_decode($receive);
                if(isset($receive->event) && $receive->event==$this::event_subscription_succeeded)
                {
                    $this->arr_channels[]=$receive->channel;
                    return true;
                }
                else
                    $this->parse_data($receive,false);
                //todo error handling
            }

        } while (time() < $timeout);
        echo $this->site.' timeout waiting for data '.$data.PHP_EOL;
        return false;
    }

    public function receive()
    {
        $data=$this->ws->receive();
        if($data===null)
            return null;
        if($data=='' || $data=='ping')
        {
            if($this->ws->getLastOpcode()=='ping')
                $this->pong();
            return null;
        }
        $datajs=json_decode($data);
        return $this->parse_data($datajs);
    }

    protected function parse_data($data,$echo=true)
    {
        switch(true)
        {
            //events
            case isset($data->event):
                switch ($data->event)
                {
                    case $this::event_connection_established:
                        $this->pusher_connected=true;
                        return $this::event_connection_established;
                    default:
                        if(in_array($data->event,$this->return_events))
                            return $data;
                        if ($echo)
                            echo $this->site.' unhandled event '.var_dump($data);
                        return $data;
                }
            //socket / connection info
            case isset($data->socket_id):
                $this->socket_id=$data->socket_id;
                if(isset($data->activity_timeout))
                    $this->activity_timeout=$data->activity_timeout;
                break;
            default:
                echo $this->site.' unhandled data '.var_dump($data);
                return $data;
        }
    }

    public function listen($duration)
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