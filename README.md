redis for webman

##  安装
```php
composer require yangpeng/webman-redis
```

## 示例

```php
use YangPeng\WebManRedis\Redis;
use support\Request;

class Index
{
    public function test(Request $request)
    {
        $name = 'test_key';
        Redis::set($name, rand());
        return response(Redis::get($name));
    }
   
}
```

## 写入缓存
```php
Redis::set('name', 'data'); // 永久有效
Redis::set('name', 'data', 600); // 有效期10分钟
```

## 查询缓存
```php
Redis::get('name');
Redis::get('name', 'default');
```

## 判断是否存在
```php
if (Redis::has('name')) {
    //
}
```

## 删除缓存
```php
Redis::delete('name');
```
## 删除多个缓存
```php
Redis::delete('name','name2');
```

## 清空缓存
```php
Redis::clear();
```