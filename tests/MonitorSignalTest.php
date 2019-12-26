<?php
require '../vendor/autoload.php';

use \MonitorSignal;

$signal = new MonitorSignal\MonitorSignal();

while(true) {
    if ($signal->monitor([SIGINT])) {//SIGINT这个信号就是当我们按了Ctrl+C的时候发出的信号
        echo "你按了Ctrl+C";
    }
    sleep(1);
    echo "我是业务代码".PHP_EOL;
}
