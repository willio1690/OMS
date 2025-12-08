<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


require_once ROOT_DIR . '/vendor/autoload.php';
use OSS\Core\OssException;
use OSS\OssClient;

class base_storage_aliyunosssystem implements base_interface_storager
{
    /**
     * 文件前缀
     * 
     * @var string
     * */
    private $_prefix = '';
    private $bucket  = '';

        /**
     * __construct
     * @param mixed $prefix prefix
     * @return mixed 返回值
     */
    public function __construct($prefix = '')
    {
        is_object($prefix) && $prefix = '';
        $this->_prefix = $prefix ?: 'common';
        $this->bucket = ALIYUNOSS_BUCKET;
        $this->url_timeout = 946080000; // 获取图片url，url的有效时间（30年）

        try {
            $this->_connect();
        } catch (Exception $e) {
            $this->ossClient = null;
        }
    }

    /**
     * 连接OSS
     * 
     * @return void
     * @author
     * */
    private function _connect()
    {
        try {
            $this->ossClient = new OssClient(ALIYUNOSS_ACCESSKEY_ID, ALIYUNOSS_ACCESSKEY_SECRET, ALIYUNOSS_ENDPOINT);
            // 设置建立连接的超时时间。默认值为10，单位为秒
            $this->ossClient->setConnectTimeout(10);
            // 设置失败请求重试次数。默认3次。
            $this->ossClient->setMaxTries(3);
            // 设置Socket层传输数据的超时时间。默认值为5184000（60天），单位为秒。
            $this->ossClient->setTimeout(600);
            // 设置是否开启SSL证书校验。false（默认值）：关闭SSL证书校验
            $this->ossClient->setUseSSL(true);
        } catch (OssException $e) {
            // print_r("Exception:" . $e->getMessage() . "\n");
            $this->ossClient = null;
        }

    }

    /**
     * 创建存储空间
     * 
     * @return void
     * @author
     * */
    public function createBucket()
    {
        if (!$this->ossClient) {
            return false;
        }
        try {
            $this->ossClient->createBucket($this->bucket);
        } catch (OssException $e) {
            return $e->getMessage();
        }
        return true;
    }

    public function save($file, &$url, $type, $addons, $ext_name = "")
    {
        if (!$this->ossClient) {
            return false;
        }

        try {
            // id = path
            $id = $this->_get_ident($file, $type, $addons, $url, $path, $ext_name);

            // $content = fopen($file, "r");

            // $this->ossClient->putObject($this->bucket, $id, $content); // 字符串上传
            $this->ossClient->uploadFile($this->bucket, $id, $file); // 文件上传

            $url = $this->ossClient->signUrl($this->bucket, $id, $this->url_timeout, "GET");
        } catch (OssException $e) {
            // print_r("Exception:" . $e->getMessage() . "\n");
            return false;
        }
        return $id;
    }

        /**
     * replace
     * @param mixed $file file
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function replace($file, $id)
    {
        if (!$this->ossClient) {
            return false;
        }
        try {

            // $content = fopen($file, "r");

            //Upload oss
            // $this->ossClient->putObject($this->bucket, $id, $content);
            $this->ossClient->uploadFile($this->bucket, $id, $file);
        } catch (OssException $e) {
            // print_r("Exception:" . $e->getMessage() . "\n");
            return false;
        }
        return $id;
    }

    /**
     * _get_ident
     * @param mixed $file file
     * @param mixed $type type
     * @param mixed $addons addons
     * @param mixed $url url
     * @param mixed $path path
     * @param mixed $ext_name ext_name
     * @return mixed 返回值
     */
    public function _get_ident($file, $type, $addons, &$url, &$path, $ext_name)
    {
        $blobName = [
            $addons['node_id'] ?: base_shopnode::node_id('ome'),
            trim($this->_prefix, '/'),
            basename($file),
        ];

        $path = trim(implode('/', $blobName), '/');


        if ($ext_name) {
            $path = $path . $ext_name;
        }

        return $path;
    }

    /**
     * remove
     * @param mixed $id ID
     * @return mixed 返回值
     */
    public function remove($id)
    {
        if (!$this->ossClient) {
            return false;
        }
        try {

            $this->ossClient->deleteObject($this->bucket, $id);
        } catch (OssException $e) {
            // print_r("Exception:" . $e->getMessage() . "\n");
            return false;
        }
        return true;
    }

    /**
     * 获取File
     * @param mixed $id ID
     * @param mixed $type type
     * @return mixed 返回结果
     */
    public function getFile($id, $type)
    {
        if (!$this->ossClient) {
            return false;
        }

        if (pathinfo($type, PATHINFO_EXTENSION)) {
            $filename = $type;
        } else {
            $filename = DATA_DIR . '/' . trim($id, '/');

            $f_dir = dirname($filename);

            if (!is_dir($f_dir)) {
                utils::mkdir_p($f_dir);
            }
        }

        try {
            $options   = array(
                OssClient::OSS_FILE_DOWNLOAD => $filename,
            );
            $this->ossClient->getObject($this->bucket, $id, $options);

            return $filename;
        } catch (OssException $e) {
            return false;
        }
        return true;
    }
}
