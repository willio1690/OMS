<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class taskmgr_storage_aliyunoss extends taskmgr_storage_abstract implements taskmgr_storage_interface
{

    private static $_storageConn = null;

    /**
     * __construct
     * @return mixed 返回值
     */
    public function __construct()
    {
        self::$_storageConn = new base_storage_aliyunosssystem('export');
    }

    /**
     * 向远程ftp上传保存生成文件
     * 
     * @param string $source_file 源文件含路径
     * @param string $task_id 目标文件名命名传入参数
     * @param string $url 生成目标文件路径
     * @return boolean true/false
     */
    public function save($source_file, $task_id, &$url)
    {
        $url = self::$_storageConn->save($source_file, $remote_url, '', []);

        return $url;
    }

    /**
     * 向远程ftp下载文件到本地
     * 
     * @param string $url 远程源文件
     * @param string $local_file 本地目标文件
     * @return boolean true/false
     */
    public function get($url, $local_file)
    {
        return self::$_storageConn->getFile($url, $local_file);
    }

    /**
     * 向远程ftp删除指定文件
     * 
     * @param string $url 远程源文件
     */
    public function delete($url)
    {
        return self::$_storageConn->remove($url);
    }

    // public function __destruct()
    // {
    //     unset(self::$_storageConn);
    // }
}
