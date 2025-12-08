<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class ediws_file_analysis_csv extends ediws_file_analysis_abstract
{
    /**
     * 读取文件内容
     * 
     * @param string $filename
     * @param string $error_msg
     */
    public function readFile($filename, &$error_msg=null)
    {
        //检查文件是否存在
        $file = $this->_checkFile($filename, $error_msg);
        if(!$file){
            return $this->error($error_msg);
        }
        
        $fp = fopen($file,'rb');

        $content = array();
       
        $line = 0;
        $data = array();
        $csv_title = array();
        $title_flag = false;
        while($fp && !feof($fp)){
            $content = fgetcsv($fp);
            
            foreach( $content as &$col ){
                $col = (string)$col;            
                $col = iconv("GBK","UTF-8",$col);
            }

            if ($title_flag == false){
                $csv_title = array_flip($content);
                $title_flag = true;
                continue;#去除第一行标题
            }

            $row = array();
            foreach($csv_title as $ck=>$cv){
               
                $row[$ck] = $content[$cv];
                
                
            }
            $data[] = $row;
            //记录当前行数
            $line++;
        }

      
        return $this->succ('succ', $data);
    }
    
    /**
     * 按行进行读取文件内容
     * 
     * @param string $csv_file 文件路径
     * @param number $lines 每页读取行数
     * @param number $offset 从哪行开始读取
     * @return boolean|multitype:multitype: |string
     */
    function read_csv_lines($csv_file='', $lines=0, $offset=0)
    {
        if(!$fp = fopen($csv_file, 'r')){
            return false;
        }
        
        $i = $j = 0;
        while(false !== ($line = fgets($fp)))
        {
            if ($i++ < $offset) {
                continue;
            }
            break;
        }
        
        $data = array();
        while(($j++ < $lines) && !feof($fp))
        {
            $data[] = fgetcsv($fp);
        }
        
        fclose($fp);
        
        return $data;
    }
}