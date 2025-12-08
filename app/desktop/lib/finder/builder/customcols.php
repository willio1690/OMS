<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class desktop_finder_builder_customcols extends desktop_finder_builder_prototype{

    function main(){
        $customcolsMdl = app::get('desktop')->model('customcols');

        if($_POST['cols']){

            // 验证入参
            foreach($_POST['cols'] as $k=>$v){
                // $v['col_key']只能是字母、数字、下划线
                if(!preg_match('/^[a-zA-Z0-9_]+$/', $v['col_key'])){
                    header('Content-Type:text/jcmd; charset=utf-8');
                    echo '{error:"保存错误,原因:字段名称【'.$v['col_key'].'】只能是字母、数字、下划线",_:null}';
                    exit;
                }

                // $v['col_name']只能是中文、英文、数字、下划线
                if(!preg_match('/^[\p{Han}a-zA-Z0-9_\(\)\（\）]+$/u', $v['col_name'])){
                    header('Content-Type:text/jcmd; charset=utf-8');
                    echo '{error:"保存错误,原因:字段描述【'.$v['col_name'].'】只能是中文、英文、数字、下划线",_:null}';
                    exit;
                }
            }

            $table_name = $this->object->table_name(1);

            $columns  = $this->object->_columns();
            $column_keys = array_keys($columns);

            $col_keys = array_column($_POST['cols'],'col_key');

            $intersection = array_intersect($col_keys, $column_keys);
            //通用默认判断
            if($intersection){
                $msg = '自定义列:'.implode(',',$intersection).'已存在';
                header('Content-Type:text/jcmd; charset=utf-8');
                echo '{error:"保存错误,原因:'.$msg.'",_:null}';
                exit;
              
            }
            if (method_exists($this->object, 'checkCustomcols')){

                list($rs,$msg) = $this->object->checkCustomcols($_POST['cols']);
                if(!$rs){
                    header('Content-Type:text/jcmd; charset=utf-8');
                    echo '{error:"保存错误,原因:'.$msg.'",_:null}';
                    exit;
                }
            }
            
            foreach($_POST['cols'] as $k=>$v){

                $v['tbl_name'] = $table_name;

                $customcolsMdl->save($v);
            }

            header('Content-Type:text/jcmd; charset=utf-8');
            echo '{success:"'.app::get('desktop')->_('设置成功').'"}';    
        }else{

           
            $render = app::get('desktop')->render();

            $render->pagedata['cols'] = $customcolsMdl->getList('*',array('tbl_name'=>$this->object->table_name(1)));

            echo $render->fetch('common/customcols.html');
        }
    }
}