<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ediws_func
{
    
    /**
     * 获取insert sql语句
     * @access static public
     * @param Object $model model对象
     * @param Array $data 需插入的关联(字段)数组数据,支持多维
     * @return String insert sql语句
     */
    static public function get_insert_sql($model,$data){
        if (empty($model) || empty($data)) return NULL;

        $cols = $model->_columns();
        $strValue = $insert_data = $column_type = array();
        $strFields = '';

        $rs = $model->db->exec('select * from `'.$model->table_name(1).'` where 0=1');
        $col_count = mysql_num_fields($rs['rs']);

        $tmp_data = $data;
        if (!is_array(array_pop($tmp_data))){
            $insert_data[] = $data;
        }else{
            $insert_data = $data;
        }
        unset($tmp_data);

        foreach ($insert_data as $key=>$value){
            $insertValues = array();
            if (!empty($strFields)){
                $col_count = count($strFields);
            }
            for($i=0;$i<$col_count;$i++) {
                if (empty($strFields)){
                    $column = mysql_fetch_field($rs['rs'],$i);
                    $k = $column->name;
                    $column_type[$k] = $column->type;
                    if( !isset($value[$k]) ){
                        continue;
                    }
                }else{
                    $k = $strFields[$i];
                }
                $p = $cols[$k];

                if(!isset($p['default']) && $p['required'] && $p['extra']!='auto_increment'){
                    if(!isset($value[$k])){
                        trigger_error(($p['label']?$p['label']:$k).app::get('base')->_('不能为空！'),E_USER_ERROR);
                    }
                }

                if( $value[$k] !== false ){
                    if( $p['type'] == 'last_modify' ){
                        $insertValues[$k] = time();
                    }elseif( $p['depend_col'] ){
                        $dependColVal = explode(':',$p['depend_col']);
                        if( $value[$dependColVal[0]] == $dependColVal[1] ){
                            switch( $dependColVal[2] ){
                                case 'now':
                                    $insertValues[$k] = time();
                                    break;
                            }
                        }
                    }
                }

                if( $p['type']=='serialize' ){
                    $value[$k] = serialize($value[$k]);
                }
                if( !isset($value[$k]) && $p['required'] && isset($p['default']) ){
                    $value[$k] = $p['default'];
                }
                $insertValues[$k] = base_db_tools::quotevalue($model->db,$value[$k],$column_type[$k]);
            }
            if (empty($strFields)){
                $strFields = array_keys($insertValues);
            }
            $strValue[] = "(".implode(',',$insertValues).")";
        }

        $strFields = implode('`,`', $strFields);
        $strValue = implode(',', $strValue);
        $sql = 'INSERT INTO `'.$model->table_name(true).'` ( `'.$strFields.'` ) VALUES '.$strValue;

        return $sql;
    }

    

    /**
     * 获取REPLACE sql语句
     * @access static public
     * @param Object $model model对象
     * @param Array $data 需插入的关联(字段)数组数据,支持多维
     * @return String insert sql语句
     */
    static public function get_replace_sql($model,$data){
        if (empty($model) || empty($data)) return NULL;
        
        $cols = $model->_columns();
       
        $strValue = $insert_data = $column_type = array();
        $strFields = '';
        
        $rs = $model->db->exec('select * from `'.$model->table_name(1).'` where 0=1');
        $col_count = mysql_num_fields($rs['rs']);
        
        $tmp_data = $data;
        if (!is_array(array_pop($tmp_data))){
            $insert_data[] = $data;
        }else{
            $insert_data = $data;
        }
        unset($tmp_data);
        
        foreach ($insert_data as $key=>$value){
            $insertValues = array();
            if (!empty($strFields)){
                $col_count = count($strFields);
            }
            for($i=0;$i<$col_count;$i++) {
                if (empty($strFields)){
                    $column = mysql_fetch_field($rs['rs'],$i);
                    $k = $column->name;
                    $column_type[$k] = $column->type;
                    if( !isset($value[$k]) ){
                        //continue;   
                    }
                }else{
                    $k = $strFields[$i];
                }
                $p = $cols[$k];
                
                if(!isset($p['default']) && $p['required'] && $p['extra']!='auto_increment'){
                    if(!isset($value[$k])){
                        trigger_error(($p['label']?$p['label']:$k).app::get('base')->_('不能为空！'),E_USER_ERROR);
                    }
                }
                
                if( $value[$k] !== false ){
                    if( $p['type'] == 'last_modify' ){
                        $insertValues[$k] = time();
                    }elseif( $p['depend_col'] ){
                        $dependColVal = explode(':',$p['depend_col']);
                        if( $value[$dependColVal[0]] == $dependColVal[1] ){
                            switch( $dependColVal[2] ){
                                case 'now':
                                    $insertValues[$k] = time();
                                    break;
                            }
                        }
                    }
                }
                
                if( $p['type']=='serialize' ){
                    $value[$k] = serialize($value[$k]);
                }
                if( !isset($value[$k]) && $p['required'] && isset($p['default']) ){
                    $value[$k] = $p['default'];
                }
                $insertValues[$k] = base_db_tools::quotevalue($model->db,$value[$k],$column_type[$k]);
            }
            if (empty($strFields)){
                $strFields = array_keys($insertValues);
            }
            $strValue[] = "(".implode(',',$insertValues).")";
        }
        
        $strFields = implode('`,`', $strFields);
       
        $strValue = implode(',', $strValue);
        $sql = 'REPLACE INTO `'.$model->table_name(true).'` ( `'.$strFields.'` ) VALUES '.$strValue;
        
        return $sql;
    }


    /**
     * 兼容PHP5.3不支持array_column()方法
     * 
     * @param array $array
     * @param $column_key
     * @param $index_key
     * @return array
     */
    public function _array_column(array $array, $column_key, $index_key=null)
    {
        if(function_exists('array_column')) {
            if($index_key){
                return array_column($array, $column_key, $index_key);
            }else{
                return array_column($array, $column_key);
            }
        }
        
        $result = array();
        foreach($array as $arr)
        {
            if(!is_array($arr)) continue;
            
            if(is_null($column_key)){
                $value = $arr;
            }else{
                $value = $arr[$column_key];
            }
            
            if(!is_null($index_key)){
                $key = $arr[$index_key];
                $result[$key] = $value;
            }else{
                $result[] = $value;
            }
        }
        
        return $result;
    }
    

    /**
     * 下载.zip压缩文件
     * 
     * @param $url
     * @param $write_file 写入的文件名
     * @return true
     */
    public static function download_zip($url, $write_file)
    {
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $data = curl_exec($ch);
        
        curl_close($ch);
        
        $file = fopen($write_file, 'w+');
        
        fputs($file, $data);
        fclose($file);
        
        return true;
    }
    
    /**
     * 解压文件
     * 
     * @param $zip_file 压缩文件名
     * @param $unzip_dir 解压目录名
     * @param $is_delete 解压后是否删除压缩文件
     * @return true
     */
    public function unZip($zip_file, $unzip_dir, $is_delete=false)
    {
        $result = array('rsp'=>'fail', 'error_msg'=>'');
        $filrname = array();
        
        //dir
        if (!is_dir($unzip_dir)) {
            utils::mkdir_p($unzip_dir);
        }
        
        //修改文件夹权限
        chmod($zip_file, 0777);
        
        $zip = new ZipArchive;
        if($zip->open($zip_file) === true) {
            //读取zip压缩包文件列表
            for($i=0; $i<$zip->numFiles; $i++){
                //$zip->numFiles 获取压缩包内文件个数（父级）
                $filrname[] = $zip->getNameIndex($i);
            }
            
            $zip->close();
        }else{
            $result['error_msg'] = '打开压缩文件失败!';
            return $result;
        }
        
        //check
        if(empty($filrname)){
            $result['error_msg'] = '读取压缩包中的文件失败';
            return $result;
        }
        
        //保存压缩包中的文件
        if ($zip->open($zip_file) === true) {
            $zip->extractTo($unzip_dir); //直接解压不修改文件名;
            //$zip->addFile('image.txt'); //指定文件名进行存储;
            
            $zip->close();
        }else{
            $result['error_msg'] = '打开压缩文件失败;';
            return $result;
        }
        
        //删除压缩文件
        if ($is_delete) {
            unlink($zip_file);
        }
        
        //files
        $result['fileList'] = $filrname;
        $result['rsp'] = 'succ';
        
        return $result;
    }
}
