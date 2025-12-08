<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_download{

    private static $__save_path = '';

    /**
     * 下载文件并保存
     * @access public
     * @param String $download_url 文件地址
     * @param String $save_path 保存路径
     * 包含目录及文件名的完整路径,为空，则自动存储到data目录里，文件名随机数,并返回
     * @return 成功返回本地文件路径(包含文件名)  失败返回false
     */
    public static function download_file($download_url,$save_path,&$msg=''){
        if (empty($download_url)){
            $msg = '下载文件地址不能为空';
            return false;
        }

        #本地文件名
        if (empty($save_path)){
            $filename = date('YmdHis-').sprintf('%u',crc32(finance_func::md5_randnums())).'.csv';
            $save_path = DATA_DIR.'/download/'.$filename;
        }
        utils::mkdir_p(dirname($save_path),0777);
        chmod(dirname($save_path),0777);
        
        $func = '_fopen';
        return self::$func($download_url,$save_path,$msg);
    }

    private static function _fopen($download_url,$save_path,&$msg){
        if (!ini_get('allow_url_fopen')){
            $msg = '当前服务器不支持allow_url_fopen';
            return false;
        }

        #读取远程文件
        if($remoteFP = fopen($download_url,'rb')){
            $save_path_arr = array(
                'transfer' => str_replace('.csv','_transfer.csv',$save_path),
                'charge' => str_replace('.csv','_charge.csv',$save_path),  
            );
            $localFP = array();
            foreach ($save_path_arr as $type=>$path){
                if(!$localFP[$type] = fopen($path,'wb')){
                    return false;
                }
            }
            $remote_content = $read_num = $csv_title = array();
            $i = 1;
            while($remoteFP && !feof($remoteFP)){
                if ($i == 1){
                    $title_line = fgets($remoteFP);
                    $csv_title = array_flip(explode('","',trim($title_line,"\"\r\n")));
                    #生成初始文件头
                    foreach ($save_path_arr as $type=>$path){
                        if(!fwrite($localFP[$type],$title_line)){
                            $msg = '本地文件头生成失败';
                            return false;
                        }
                    }
                    $i++;
                    continue;
                }

                #将数据分类插入到文件
                foreach ($save_path_arr as $type=>$path){
                    if ($read_num[$type] >= 100){
                        if(!fwrite($localFP[$type],$remote_content[$type])){
                            $msg = '本地文件内容生成失败';
                            return false;
                        }else{
                            $read_num[$type] = '0';
                            $remote_content[$type] = NULL;
                        }
                    }
                }

                #组织数据
                $csv_content_str = fgets($remoteFP);
                $csv_content_arr = explode('","',trim($csv_content_str,"\"\r\n"));
                $csv_type = $csv_content_arr[$csv_title['type']];
                //$remote_content[$csv_type] .= iconv('GB2312','UTF-8//IGNORE',$csv_content_str);
                $remote_content[$csv_type] .= $csv_content_str;
                $read_num[$csv_type]++;

                $i++;
            }

            #存储剩余文件内容
            foreach ($save_path_arr as $type=>$path){
                if ($remote_content[$type]){
                    if(!fwrite($localFP[$type],$remote_content[$type])){
                        $msg = '本地文件内容生成失败';
                        return false;
                    }
                }
                fclose($localFP[$type]);
            }
            fclose($remoteFP);
            self::$__save_path = $save_path;
            return $save_path;
        }else{
            $msg = '下载文件失败';
            return false;
        }
    }

    private static function _fsockopen($download_url,$save_path,&$msg){
        $url = parse_url($download_url);
        $host = $url['host'];
        $file = $url['path'];
        $port = $url['port'] ? $url['port'] : '80';
        $remoteFP = fsockopen($host,$port,$errno,$errstr,30);
        if($remoteFP)
        {
            $header = "GET $file HTTP/1.1\r\n";
            $header .= "Host: $host\r\n";
            $header .= "Connection: Keep-Alive\r\n\r\n";

            fwrite($remoteFP, $header);

            $save_path_arr = array(
                'transfer' => str_replace('.csv','_transfer.csv',$save_path),
                'charge' => str_replace('.csv','_charge.csv',$save_path),  
            );
            $localFP = array();
            foreach ($save_path_arr as $type=>$path){
                if(!$localFP[$type] = fopen($path,'wb')){
                    return false;
                }
            }
            $remote_content = $read_num = $csv_title = array();
            $i = 1;
            $read = false;
            while($remoteFP && !feof($remoteFP)){
                if ($read == false && fgets($remoteFP) == "\r\n"){
                    $read = true;
                    continue;
                }elseif($read == false){
                    continue;
                }

                if ($i == 1){
                    $title_line = fgets($remoteFP);
                    $csv_title = array_flip(explode('","',trim($title_line,"\"\r\n")));
                    #生成初始文件头
                    foreach ($save_path_arr as $type=>$path){
                        if(!fwrite($localFP[$type],$title_line)){
                            $msg = '本地文件头生成失败';
                            return false;
                        }
                    }
                    $i++;
                    continue;
                }

                #将数据分类插入到文件
                foreach ($save_path_arr as $type=>$path){
                    if ($read_num[$type] >= 100){
                        if(!fwrite($localFP[$type],$remote_content[$type])){
                            $msg = '本地文件内容生成失败';
                            return false;
                        }else{
                            $read_num[$type] = '0';
                            $remote_content[$type] = NULL;
                        }
                    }
                }

                #组织数据
                $csv_content_str = fgets($remoteFP);
                $csv_content_arr = explode('","',trim($csv_content_str,"\"\r\n"));
                $csv_type = $csv_content_arr[$csv_title['type']];
                //$remote_content[$csv_type] .= iconv('GB2312','UTF-8//IGNORE',$csv_content_str);
                $remote_content[$csv_type] .= $csv_content_str;
                $read_num[$csv_type]++;

                $i++;
            }

            #存储剩余文件内容
            foreach ($save_path_arr as $type=>$path){
                if ($remote_content[$type]){
                    if(!fwrite($localFP[$type],$remote_content[$type])){
                        $msg = '本地文件内容生成失败';
                        return false;
                    }
                }
                fclose($localFP[$type]);
            }
            fclose($remoteFP);
            self::$__save_path = $save_path;
            return $save_path;
        }else{
            $msg = '远程主机连接失败:'.$errstr;
            return false;
        }
    }

    /**
     * 删除下载文件
     * @access public 
     * @param $file_path 文件路径
     * @return bool
     */
    public static function rm_file($file_path){
        $file_path = $file_path ? $file_path : self::$__save_path;
        echo $file_path;
        $save_path_arr = array(
            'transfer' => str_replace('.csv','_transfer.csv',$file_path),
            'charge' => str_replace('.csv','_charge.csv',$file_path),
        );
        foreach ($save_path_arr as $type=>$path){
            if (file_exists($path)){
                @unlink($path);
            }
        }
        if (file_exists($file_path)){
            return @unlink($file_path);
        }
        return true;
    }

}