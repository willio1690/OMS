<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


/**
 * 网店配置模板
 *
 * 版本 $Id: config.sample.php 37482 2009-12-08 10:54:56Z ever $
 * 配置参数讨论专贴 http://www.shopex.cn/bbs/thread-61957-1-1.html
 */


// 可配置项总览（envMap 已集中定义；下方保留逐条配置作对照）
// 推荐在 .env 中设置以下 key，系统按 env 优先、无值用 default。

// 读取同级/上级 .env（支持 key=value），便于本地安装不改代码
if (!function_exists('__load_env_if_exists')) {
    function __load_env_if_exists(array $paths)
    {
        foreach ($paths as $envFile) {
            if (!$envFile || !is_readable($envFile)) {
                continue;
            }
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(ltrim($line), '#') === 0) {
                    continue;
                }
                $parts = explode('=', $line, 2);
                if (count($parts) !== 2) {
                    continue;
                }
                $k = trim($parts[0]);
                $v = trim($parts[1]);
                // 去掉包裹引号
                if ((str_starts_with($v, '"') && str_ends_with($v, '"')) || (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
                    $v = substr($v, 1, -1);
                }
                putenv($k.'='.$v);
                $_ENV[$k] = $v;
            }
        }
    }
}
$envCandidates = array(
    __DIR__.'/.env',
);

// 去重
$envCandidates = array_values(array_unique(array_filter($envCandidates)));
__load_env_if_exists($envCandidates);
// 数据库      : 用户/密码/库名/主机/持久连接                -> ONEX_OMS_DB_USER / ONEX_OMS_DB_PASSWORD / ONEX_OMS_DB_NAME / ONEX_OMS_DB_HOST / ONEX_OMS_DB_PCONNECT
// 运行控制    : URL 重写、密钥、表前缀、时区、禁缓存/禁 kv    -> ONEX_OMS_WITH_REWRITE / ONEX_OMS_STORE_KEY / ONEX_OMS_DB_PREFIX / ONEX_OMS_DEFAULT_TIMEZONE / ONEX_OMS_WITHOUT_CACHE / ONEX_OMS_WITHOUT_KVSTORE_PERSISTENT
// 调优        : 字符集、排序、前端调试、HTTPS 支持            -> ONEX_OMS_DB_CHARSET / ONEX_OMS_DB_COLLATE / ONEX_OMS_DEBUG_JS / ONEX_OMS_WITH_HTTPS_SUPPORT
// 目录常量    : 根目录自动计算，其余可改                     -> ROOT_DIR / DATA_DIR / THEME_DIR / PUBLIC_DIR / MEDIA_DIR / SECACHE_SIZE / MAIL_LOG / DEFAULT_INDEX / SERVER_TIMEZONE / WITHOUT_GZIP / WITHOUT_STRIP_HTML
// 日志        : 级别/类型/格式/日志文件/头部                 -> ONEX_OMS_LOG_LEVEL / ONEX_OMS_LOG_TYPE / ONEX_OMS_LOG_FORMAT / LOG_FILE / LOG_HEAD_TEXT
// KV          : 后端选择、前缀、memcache/memcached/redis 配置 -> ONEX_OMS_KVSTORE_STORAGE / ONEX_OMS_KV_PREFIX / ONEX_OMS_KVSTORE_MEMCACHE_CONFIG / ONEX_OMS_KVSTORE_MEMCACHED_CONFIG / ONEX_OMS_KVSTORE_REDIS_CONFIG / ONEX_OMS_KVSTORE_REDIS_AUTH
// Cache       : 后端选择及各存储配置                         -> ONEX_OMS_CACHE_STORAGE / ONEX_OMS_CACHE_MEMCACHE_CONFIG / ONEX_OMS_CACHE_MEMCACHED_CONFIG / ONEX_OMS_CACHE_REDIS_CONFIG / ONEX_OMS_CACHE_REDIS_AUTH / ONEX_OMS_CACHE_ALICACHE_CONFIG / ONEX_OMS_CACHE_XCACHE_CONFIG / ONEX_OMS_CACHE_WINCACHE_CONFIG / ONEX_OMS_CACHE_EACCELERATOR_CONFIG / ONEX_OMS_CACHE_APC_CONFIG / ONEX_OMS_CACHE_ECAE_CONFIG / ONEX_OMS_CACHE_SECACHE_CONFIG
// 可选功能    : 安全模式/模板/入口/代理/触发器/从库/镜像/存储/压缩/静态/日志 -> ONEX_OMS_SAFE_MODE / ONEX_OMS_TEMPLATE_MODE / ONEX_OMS_APP_ROOT_PHP / ONEX_OMS_HTTP_PROXY / ONEX_OMS_TRIGGER_LOG / ONEX_OMS_DISABLE_TRIGGER / ONEX_OMS_BLACKLIST / ONEX_OMS_DB_SLAVE_NAME / ONEX_OMS_DB_SLAVE_USER / ONEX_OMS_DB_SLAVE_PASSWORD / ONEX_OMS_DB_SLAVE_HOST / ONEX_OMS_HOST_MIRRORS / ONEX_OMS_WITH_STORAGER / ONEX_OMS_GZIP_CSS / ONEX_OMS_GZIP_JS / ONEX_OMS_FILE_STORAGER / ONEX_OMS_STORAGE_MEMCACHED / ONEX_OMS_APP_STATICS_HOST / ONEX_OMS_MONOLOG_OPTIONS

// ** envMap 集中定义（执行后续仍保留逐条配置，便于对照） ** //
$defaultRootDir = realpath(dirname(__FILE__) . '/../');
$envMap = array(
    // DB 基础
    'DB_USER'                    => array(
        'env'     => 'ONEX_OMS_DB_USER',
        'default' => 'usernamehere',
    ), # DB 用户
    'DB_PASSWORD'                => array(
        'env'     => 'ONEX_OMS_DB_PASSWORD',
        'default' => 'yourpasswordhere',
    ), # DB 密码
    'DB_NAME'                    => array(
        'env'     => 'ONEX_OMS_DB_NAME',
        'default' => 'putyourdbnamehere',
    ), # DB 名
    'DB_HOST'                    => array(
        'env'     => 'ONEX_OMS_DB_HOST',
        'default' => 'localhost',
    ), # DB 主机
    'DB_PCONNECT'                => array(
        'env'     => 'ONEX_OMS_DB_PCONNECT',
        'default' => null,
        'bool'    => true,
    ), # 持久连接开关

    // 运行时控制
    'WITH_REWRITE'               => array(
        'env'     => 'ONEX_OMS_WITH_REWRITE',
        'default' => false,
        'bool'    => true,
    ), # URL 重写
    'STORE_KEY'                  => array(
        'env'     => 'ONEX_OMS_STORE_KEY',
        'default' => '',
    ), # 密钥
    'DB_PREFIX'                  => array(
        'env'     => 'ONEX_OMS_DB_PREFIX',
        'default' => 'sdb_',
    ), # 表前缀
    'DEFAULT_TIMEZONE'           => array(
        'env'     => 'ONEX_OMS_DEFAULT_TIMEZONE',
        'default' => '8',
    ), # 默认时区
    'WITHOUT_CACHE'              => array(
        'env'     => 'ONEX_OMS_WITHOUT_CACHE',
        'default' => false,
        'bool'    => true,
    ), # 全局禁缓存
    'WITHOUT_KVSTORE_PERSISTENT' => array(
        'env'     => 'ONEX_OMS_WITHOUT_KVSTORE_PERSISTENT',
        'default' => false,
        'bool'    => true,
    ), # 禁 kv 持久

    // 调优参数
    'DB_CHARSET'                 => array(
        'env'     => 'ONEX_OMS_DB_CHARSET',
        'default' => 'utf8mb4',
    ), # 字符集
    'DB_COLLATE'                 => array(
        'env'     => 'ONEX_OMS_DB_COLLATE',
        'default' => '',
    ), # 排序规则
    'DEBUG_JS'                   => array(
        'env'     => 'ONEX_OMS_DEBUG_JS',
        'default' => false,
        'bool'    => true,
    ), # 前端调试
    'WITH_HTTPS_SUPPORT'         => array(
        'env'     => 'ONEX_OMS_WITH_HTTPS_SUPPORT',
        'default' => 'Off',
    ), # HTTPS 支持

    // 目录与常量
    'ROOT_DIR'                   => array(
        'default' => $defaultRootDir,
    ), # 根目录
    'DATA_DIR'                   => array(
        'default' => $defaultRootDir.'/data',
    ), # 数据目录
    'THEME_DIR'                  => array(
        'default' => $defaultRootDir.'/themes',
    ), # 主题目录
    'PUBLIC_DIR'                 => array(
        'default' => $defaultRootDir.'/public',
    ), # 公共目录
    'MEDIA_DIR'                  => array(
        'default' => $defaultRootDir.'/public/images',
    ), # 媒体目录
    'SECACHE_SIZE'               => array(
        'default' => '1024M',
    ), # secache 大小
    'MAIL_LOG'                   => array(
        'default' => false,
    ), # 邮件日志开关
    'DEFAULT_INDEX'              => array(
        'default' => '',
    ), # 默认首页
    'SERVER_TIMEZONE'            => array(
        'default' => 8,
    ), # 服务器时区
    'WITHOUT_GZIP'               => array(
        'default' => false,
    ), # 关闭 GZIP
    'WITHOUT_STRIP_HTML'         => array(
        'default' => true,
    ), # 关闭 strip html

    // 日志
    'LOG_FILE'                   => array(
        'default' => $defaultRootDir.'/data/logs/{date}/{ip}.php',
    ), # 日志文件
    'LOG_HEAD_TEXT'              => array(
        'default' => '<' . '?php exit()?' . ">\n",
    ), # 日志头
    'LOG_LEVEL'                  => array(
        'env'     => 'ONEX_OMS_LOG_LEVEL',
        'default' => null,
    ), # 日志级别
    'LOG_TYPE'                   => array(
        'env'     => 'ONEX_OMS_LOG_TYPE',
        'default' => null,
    ), # 日志类型
    'LOG_FORMAT'                 => array(
        'env'     => 'ONEX_OMS_LOG_FORMAT',
        'default' => null,
    ), # 日志格式

    // kvstore / cache
    'KVSTORE_STORAGE'            => array(
        'env'     => 'ONEX_OMS_KVSTORE_STORAGE',
        'default' => 'base_kvstore_filesystem',
    ), # kv 存储
    'KVSTORE_MEMCACHE_CONFIG'    => array(
        'env'     => 'ONEX_OMS_KVSTORE_MEMCACHE_CONFIG',
        'default' => null,
    ), # kv memcache 配置
    'KVSTORE_MEMCACHED_CONFIG'   => array(
        'env'     => 'ONEX_OMS_KVSTORE_MEMCACHED_CONFIG',
        'default' => null,
    ), # kv memcached 配置
    'KVSTORE_REDIS_CONFIG'       => array(
        'env'     => 'ONEX_OMS_KVSTORE_REDIS_CONFIG',
        'default' => null,
    ), # kv redis 配置
    'KVSTORE_REDIS_AUTH'         => array(
        'env'     => 'ONEX_OMS_KVSTORE_REDIS_AUTH',
        'default' => null,
    ), # kv redis 密码

    'CACHE_STORAGE'              => array(
        'env'     => 'ONEX_OMS_CACHE_STORAGE',
        'default' => 'base_cache_secache',
    ), # 缓存存储
    'KV_PREFIX'                  => array(
        'env'     => 'ONEX_OMS_KV_PREFIX',
        'default' => null,
    ), # kv 前缀
    'CACHE_MEMCACHE_CONFIG'      => array(
        'env'     => 'ONEX_OMS_CACHE_MEMCACHE_CONFIG',
        'default' => null,
    ), # cache memcache 配置
    'CACHE_MEMCACHED_CONFIG'     => array(
        'env'     => 'ONEX_OMS_CACHE_MEMCACHED_CONFIG',
        'default' => null,
    ), # cache memcached 配置
    'CACHE_REDIS_CONFIG'         => array(
        'env'     => 'ONEX_OMS_CACHE_REDIS_CONFIG',
        'default' => null,
    ), # cache redis 配置
    'CACHE_REDIS_AUTH'           => array(
        'env'     => 'ONEX_OMS_CACHE_REDIS_AUTH',
        'default' => null,
    ), # cache redis 密码
    'CACHE_ALICACHE_CONFIG'      => array(
        'env'     => 'ONEX_OMS_CACHE_ALICACHE_CONFIG',
        'default' => null,
    ), # alicache 配置
    'CACHE_XCACHE_CONFIG'        => array(
        'env'     => 'ONEX_OMS_CACHE_XCACHE_CONFIG',
        'default' => null,
    ), # xcache 配置
    'CACHE_WINCACHE_CONFIG'      => array(
        'env'     => 'ONEX_OMS_CACHE_WINCACHE_CONFIG',
        'default' => null,
    ), # wincache 配置
    'CACHE_EACCELERATOR_CONFIG'  => array(
        'env'     => 'ONEX_OMS_CACHE_EACCELERATOR_CONFIG',
        'default' => null,
    ), # eaccelerator 配置
    'CACHE_APC_CONFIG'           => array(
        'env'     => 'ONEX_OMS_CACHE_APC_CONFIG',
        'default' => null,
    ), # apc 配置
    'CACHE_ECAE_CONFIG'          => array(
        'env'     => 'ONEX_OMS_CACHE_ECAE_CONFIG',
        'default' => null,
    ), # ecae 配置
    'CACHE_SECACHE_CONFIG'       => array(
        'env'     => 'ONEX_OMS_CACHE_SECACHE_CONFIG',
        'default' => null,
    ), # secache 配置

    // Session
    'SESS_NAME'                  => array(
        'env'     => 'ONEX_OMS_SESS_NAME',
        'default' => null,
    ), # session 名称
    'SESS_CACHE_EXPIRE'          => array(
        'env'     => 'ONEX_OMS_SESS_CACHE_EXPIRE',
        'default' => null,
    ), # session 过期（分钟）

    // 其他可选
    'SAFE_MODE'                  => array(
        'env'     => 'ONEX_OMS_SAFE_MODE',
        'default' => null,
        'bool'    => true,
    ), # 安全模式
    'TEMPLATE_MODE'              => array(
        'env'     => 'ONEX_OMS_TEMPLATE_MODE',
        'default' => null,
    ), # 模板模式
    'APP_ROOT_PHP'               => array(
        'env'     => 'ONEX_OMS_APP_ROOT_PHP',
        'default' => null,
    ), # 自定义入口
    'HTTP_PROXY'                 => array(
        'env'     => 'ONEX_OMS_HTTP_PROXY',
        'default' => null,
    ), # HTTP 代理
    'TRIGGER_LOG'                => array(
        'env'     => 'ONEX_OMS_TRIGGER_LOG',
        'default' => null,
        'bool'    => true,
    ), # 触发器日志
    'DISABLE_TRIGGER'            => array(
        'env'     => 'ONEX_OMS_DISABLE_TRIGGER',
        'default' => null,
        'bool'    => true,
    ), # 禁用触发器
    'BLACKLIST'                  => array(
        'env'     => 'ONEX_OMS_BLACKLIST',
        'default' => null,
    ), # 前台黑名单
    'DB_SLAVE_NAME'              => array(
        'env'     => 'ONEX_OMS_DB_SLAVE_NAME',
        'default' => null,
    ), # 从库名
    'DB_SLAVE_USER'              => array(
        'env'     => 'ONEX_OMS_DB_SLAVE_USER',
        'default' => null,
    ), # 从库用户
    'DB_SLAVE_PASSWORD'          => array(
        'env'     => 'ONEX_OMS_DB_SLAVE_PASSWORD',
        'default' => null,
    ), # 从库密码
    'DB_SLAVE_HOST'              => array(
        'env'     => 'ONEX_OMS_DB_SLAVE_HOST',
        'default' => null,
    ), # 从库主机
    'HOST_MIRRORS'               => array(
        'env'     => 'ONEX_OMS_HOST_MIRRORS',
        'default' => null,
    ), # 镜像域名
    'WITH_STORAGER'              => array(
        'env'     => 'ONEX_OMS_WITH_STORAGER',
        'default' => null,
    ), # 存储驱动
    'GZIP_CSS'                   => array(
        'env'     => 'ONEX_OMS_GZIP_CSS',
        'default' => null,
        'bool'    => true,
    ), # gzip CSS
    'GZIP_JS'                    => array(
        'env'     => 'ONEX_OMS_GZIP_JS',
        'default' => null,
        'bool'    => true,
    ), # gzip JS
    'FILE_STORAGER'              => array(
        'env'     => 'ONEX_OMS_FILE_STORAGER',
        'default' => null,
    ), # 文件存储
    'STORAGE_MEMCACHED'          => array(
        'env'     => 'ONEX_OMS_STORAGE_MEMCACHED',
        'default' => null,
    ), # memcached 节点
    'APP_STATICS_HOST'           => array(
        'env'     => 'ONEX_OMS_APP_STATICS_HOST',
        'default' => null,
    ), # 静态资源 host
    'MONOLOG_OPTIONS'            => array(
        'env'     => 'ONEX_OMS_MONOLOG_OPTIONS',
        'default' => null,
    ), # Monolog 配置
);

if (!function_exists('__env_or_default')) {
    function __env_or_default($key, $default = null, $asBool = false)
    {
        $v = getenv($key);
        if ($v === false) {
            $v = null;
        }
        if ($v === null) {
            $v = $default;
        }
        if ($asBool && $v !== null) {
            $v = in_array(strtolower((string)$v), array('1','true','on','yes'), true);
        }
        return $v;
    }
}

foreach ($envMap as $name => $cfg) {
    if (defined($name)) {
        continue;
    }
    $val = null;
    if (is_array($cfg)) {
        $asBool = !empty($cfg['bool']);
        if (isset($cfg['env'])) {
            $val = __env_or_default($cfg['env'], array_key_exists('default', $cfg) ? $cfg['default'] : null, $asBool);
        } elseif (array_key_exists('default', $cfg)) {
            $val = $cfg['default'];
            if ($asBool && $val !== null) {
                $val = (bool)$val;
            }
        }
    } else {
        $val = $cfg;
    }
    if ($val !== null) {
        define($name, $val);
    }
}

// ** 数据库配置 ** //
@ini_set('memory_limit','32M');
