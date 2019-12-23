<?php

namespace monitorSignal;

class MonitorSignal
{
    public $monitorSignal = -1;//记录监听到的信号
    public $signals = [SIGHUP, SIGINT, SIGQUIT, SIGILL, SIGTRAP, SIGABRT, SIGEMT, SIGFPE,
        SIGKILL, SIGBUS, SIGSEGV, SIGSYS, SIGPIPE, SIGALRM, SIGTERM, SIGURG, SIGSTOP, SIGTSTP,
        SIGCONT, SIGCHLD, SIGTTIN, SIGTTOU, SIGIO, SIGXCPU, SIGXFSZ, SIGVTALRM, SIGPROF, SIGWINCH,
        SIGINFO, SIGUSR1, SIGUSR2
    ];//常用信号，可以通过kill -l命令查看

    //通过别名来调用监听信号的类型
    public function monitor(string $abstract)
    {
        pcntl_async_signals(true);//开启异步监听
        switch ($abstract) {
            case 'quit':
                return $this->monitorQuitSignal();//监听退出的信号
            case 'any':
                return $this->monitorAny();//监听所有类型的信号
            default :
                return 'unknow';
        }
    }

    //获取当前监听到的信号
    public function getSignal()
    {
        return $this->monitorSignal;
    }

    //回调
    public function callBackFunc($signal)
    {
        $this->monitorSignal = $signal;
        if (in_array($signal, [SIGQUIT, SIGTERM])) {
            $this->killSignal();
        }
    }

    //这是一种场景，当一个进程收到SIGQUIT、SIGTERM信号时，让进程睡眠20s。
    //比如当要终止一个容器运行的时候，Docker会首先向容器发送一个信号，然后等待一段超时时间(默认10s)后，再发送SIGKILL信号来终止容器
    //因此为了避免数据丢失，当收到SIGQUIT、SIGTERM信号时，让进程睡眠，不再执行
    private function killSignal()
    {
        while (true) {
            if (in_array($this->getSignal(), [SIGQUIT, SIGTERM])) {
                sleep(20);
            }
        }
    }

    //安装所有常用的信号处理器
    public function monitorAny()
    {
        foreach ($this->signals as $signal) {
            pcntl_signal($signal, array($this, 'callBackFunc'));
        }
        return $this;
    }

    //监听【关闭进程的信号】
    public function monitorQuitSignal()
    {
        //安装信号处理器
        pcntl_signal(SIGQUIT, array($this, 'callBackFunc'));
        pcntl_signal(SIGTERM, array($this, 'callBackFunc'));

        return $this;
    }
}