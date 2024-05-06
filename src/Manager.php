<?php

namespace YangPeng\WebManRedis;

use DateInterval;
use DateTime;
use DateTimeInterface;
use RedisException;

/**
 * 缓存管理
 */
class Manager
{
    protected \Redis $handler;

    /**
     * 配置参数
     * @var array
     */
    protected array $options = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
        'database' => 0,
        'timeout' => 0,
        'expire' => 0,
        'persistent' => false,
        'prefix' => '',
        'serialize' => [],
    ];

    /**
     * 配置参数-用于维持心跳
     * @param array $options
     * @return void
     */
    public function config($options)
    {
        $this->options = $options;
    }

    /**
     * 架构函数
     * @param array $options
     * @throws RedisException
     */
    public function __construct($options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }

        if (extension_loaded('redis')) {
            $this->handler = new \Redis;

            if ($this->options['persistent']) {
                $this->handler->pconnect($this->options['host'], (int)$this->options['port'], (int)$this->options['timeout'], 'persistent_id_' . $this->options['database']);
            } else {
                $this->handler->connect($this->options['host'], (int)$this->options['port'], (int)$this->options['timeout']);
            }

            if ($this->options['password'] != '') {
                $this->handler->auth($this->options['password']);
            }
        } else {
            throw new RedisException('未安装redis扩展', 100);
        }

        if ($this->options['database'] != 0) {
            $this->handler->select((int)$this->options['database']);
        }
    }

    /**
     * 判断缓存
     * @param string $name 缓存标识
     * @return bool
     * @throws RedisException
     */
    public function has($name)
    {
        return $this->handler->exists($this->getCacheKey($name));
    }

    /**
     * 读取缓存
     * @param string $name 缓存标识
     * @param mixed|null $default 默认值
     * @return mixed
     * @throws RedisException
     */
    public function get($name, $default = null)
    {
        $key = $this->getCacheKey($name);
        $data = $this->handler->get($key);

        if ($data === false || is_null($data)) {
            return $default;
        }

        return $this->deserialize($data);
    }

    /**
     * 写入缓存
     * @param string $name 缓存标识
     * @param mixed $data 缓存数据
     * @param int|DateInterval|DateTimeInterface|null $expire 有效时间（秒）
     * @return bool
     * @throws RedisException
     */
    public function set($name, $data, $expire = null)
    {
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }

        $key = $this->getCacheKey($name);
        $expire = $this->getExpireTime($expire);
        $data = $this->serialize($data);

        if ($expire) {
            $this->handler->setex($key, $expire, $data);
        } else {
            $this->handler->set($key, $data);
        }

        return true;
    }

    /**
     * 删除缓存
     * @param string $name 缓存标识
     * @throws RedisException
     */
    public function delete(...$name)
    {
        foreach ($name as $value) {
            $key = $this->getCacheKey($value);
            $this->handler->del($key);
        }
    }

    /**
     * 清除缓存
     * @return bool
     * @throws RedisException
     */
    public function clear()
    {
        $this->handler->flushDB();
        return true;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * 获取实际的缓存标识
     * @param string $name 缓存标识
     * @return string
     */
    public function getCacheKey($name)
    {
        return $this->options['prefix'] . $name;
    }

    /**
     * 获取有效期
     * @param int|DateInterval|DateTimeInterface $expire 有效期
     * @return int
     */
    protected function getExpireTime($expire)
    {
        if ($expire instanceof DateTimeInterface) {
            $expire = $expire->getTimestamp() - time();
        } elseif ($expire instanceof DateInterval) {
            $expire = DateTime::createFromFormat('U', (string)time())
                    ->add($expire)
                    ->format('U') - time();
        }
        return $expire;
    }

    /**
     * 序列化数据
     * @param mixed $data
     * @return string
     */
    protected function serialize($data)
    {
        if (is_numeric($data)) {
            return (string)$data;
        }
        $serialize = $this->options['serialize'][0] ?? "serialize";
        return $serialize($data);
    }

    /**
     * 反序列化数据
     * @param string $data 缓存数据
     * @return mixed
     */
    protected function deserialize($data)
    {
        if (is_numeric($data)) {
            return $data;
        }
        $deserialize = $this->options['serialize'][1] ?? "unserialize";
        return $deserialize($data);
    }

}