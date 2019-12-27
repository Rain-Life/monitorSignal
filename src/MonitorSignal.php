<?php

namespace MonitorSignal;

use GuzzleHttp\RequestOptions;
use GuzzleHttp\Client;

class MonitorSignal
{
    const TOKEN = '';//在企业微信群中创建机器人，会生成一个token
    const URL = '';//填写自己的接收报警的地址即可
    protected $monitorSignal = [];//记录所有监听到的信号
    protected $signals = [SIGHUP, SIGINT, SIGQUIT, SIGILL, SIGTRAP, SIGABRT, SIGFPE,
        SIGBUS, SIGSEGV, SIGSYS, SIGPIPE, SIGALRM, SIGTERM, SIGURG, SIGTSTP,
        SIGCONT, SIGCHLD, SIGTTIN, SIGTTOU, SIGIO, SIGXCPU, SIGXFSZ, SIGVTALRM, SIGPROF, SIGWINCH, SIGUSR1, SIGUSR2
    ];//其中6和19这两个信号不能被安装信号处理器

    public function __construct()
    {
        pcntl_async_signals(true);//开启异步监听
        $this->monitorAny();
    }

    public function monitor(array $signals) : array
    {
        $intersect = array_intersect($this->monitorSignal, $signals);
        foreach ($intersect as $k=>$v) {
            unset($this->monitorSignal[$k]);
        }

        return $intersect;
    }

    public function quit()
    {
        $quitSignalArr = array_intersect($this->monitorSignal, [SIGQUIT, SIGTERM]);
        if (!empty($quitSignalArr)) {
            //从信号数组中将接收到的信号删掉
            foreach ($quitSignalArr as $k=>$v) {
                unset($this->monitorSignal[$k]);
            }

            $this->killSignal();
        }
    }

    public function getSignal() : array
    {
        return $this->monitorSignal;
    }

    public function callBackFunc(int $signal)
    {
        if(!in_array($signal, $this->monitorSignal)) {
            $this->monitorSignal[] = $signal;
        }

    }

    public function killSignal()
    {
        $time = 0;
        $totalTime = 0;
        while (true) {
            sleep(5);
            $totalTime+=5;
            $content = "<font color='warning'>守护进程报警</font>" . "\n";
            $content .= "项目：<font color='warning'>".$_SERVER['APP_NAME']."</font>" .  "\n";
            $content .= "脚本：<font color='warning'>".$_SERVER['argv'][1]."</font>" . "\n";
            $content .= '守护进程'.getmypid().'收到退出(SIGQUIT、SIGTERM)信号，已经过了'.$totalTime.'，还未接收到SIGKILL信号';
            $this->sendMessage($content, self::TOKEN);
            $time++;
            if ($time >= 2) {//这个可以自行配置
                break;
            }
        }
    }

    //安装所有常用的信号处理器
    public function monitorAny()
    {
        foreach ($this->signals as $signal) {
            pcntl_signal($signal, array($this, 'callBackFunc'));
        }
    }

    //发送DingDing消息
    public function sendMessage($content, $token='') {
        $client = new Client();
        $options[RequestOptions::JSON] = [
            "msgtype" => "markdown",
            "markdown" => [
                'content' =>$content
            ]
        ];
        $uri = self::URL.$token;

        $client->request('POST', $uri, $options);
    }
}
