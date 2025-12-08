<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


abstract class ediws_file_analysis_abstract
{
    //本地存储文件目录
    public $local_path = DATA_DIR;
    
    //远程下载文件公共目录
    //public $remote_download_path = REMOTE_DOWNLOAD_PATH;
    
    //远程上传文件公共目录
    //public $remote_upload_path = REMOTE_UPLOAD_PATH;
    
    /**
     * 检查文件是否存在
     * 
     * @param string $filename
     * @param string $error_msg
     * @return file
     */
    public function _checkFile($filename, &$error_msg=null)
    {
        $file = $this->local_path . $filename;
        if(!file_exists($file)){
            $error_msg = '文件不存在('. $filename .')';
            return false;
        }
        
        return $file;
    }
    
    /**
     * 成功输出
     * 
     * @param string $msg
     * @param string $data
     * @return array
     */
    final public function succ($msg='', $data=null)
    {
        return array('rsp'=>'succ', 'msg'=>$msg, 'data'=>$data);
    }
    
    /**
     * 失败输出
     * 
     * @param string $msg
     * @param string $data
     * @return array
     */
    final public function error($error_msg, $data=null)
    {
        return array('rsp'=>'fail', 'msg'=>$error_msg, 'error_msg'=>$error_msg, 'data'=>$data);
    }
    
    /**
     * 生成文件
     * 
     * @param string $filename
     * @param string $content
     * @return boolean
     */
    public function create_file($filename, $content, &$error_msg='')
    {
        $fok = $filename;
        
        $fp = fopen($fok, "a+");
        
        flock($fp, LOCK_EX);
        
        $tmp = fgets($fp);
        
        if(fwrite($fp, $content)){
            flock($fp, LOCK_UN);
            
            fclose($fp);
            
            //$fok = substr($fok, strlen(DATA_DIR));
            //$error_msg = "创建新文件". $fok ."成功";
            
            return true;
        }else{
            flock($fp, LOCK_UN);
            
            fclose($fp);
            
            $fok = substr($fok, strlen(DATA_DIR));
            
            $error_msg = "创建新文件". $fok ."失败";
            return false;
        }
    }
}