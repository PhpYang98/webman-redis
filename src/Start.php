<?php

namespace YangPeng\WebManRedis;

use Webman\Bootstrap;
use Workerman\Timer;

/**
 * 进程启动
 */
class Start implements Bootstrap
{
    public static function start($worker): void
    {
        $config = config('plugin.yangpeng.webman-redis.app');
        if (!$config) {
            return;
        }
        // 配置参数
        Redis::config($config);
        // 维持心跳
        if ($worker) {
            Timer::add(55, function () {
                Redis::get('ping');
            });
        }
    }
}