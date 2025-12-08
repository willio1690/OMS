<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

abstract class openapi_api_function_abstract{
    /**
     * charFilter
     * @param mixed $str str
     * @return mixed 返回值
     */
    public function charFilter($str){
        if(strpos($str, kernel::single('ome_security_hash')->get_code())) {
            //return kernel::single('ome_func')->getEncryptText($str);
            
            //加密信息也有包含\反斜杠的情况
            $str = kernel::single('ome_func')->getEncryptText($str);
        }
        return str_replace(array("\t","\r","\n",'"',"\\",''),array(" "," "," ",'“',"/",''),$str);
    }
    #获取所有仓库,type等于1是自建仓库
    /**
     * 获取_all_branchs
     * @param mixed $type type
     * @param mixed $branch_bn branch_bn
     * @param mixed $page_no page_no
     * @param mixed $page_size page_size
     * @return mixed 返回结果
     */
    public function get_all_branchs($type = '1',$branch_bn='',$page_no=0,$page_size=-1){
        #基础条件，必须是自建仓库
        $filter = array('owner'=>'1');
        $is_super = kernel::single('desktop_user')->is_super();
        #按操作员
        if(!$is_super){
            $opInfo = kernel::single('ome_func')->getDesktopUser(1);
            $op_id = $opInfo['op_id'];
            $filter['op_id']= $op_id;
        }
        #按仓库编号
        if($branch_bn){
            $filter['branch_bn'] = $branch_bn;
        }
        $branchObj = app::get('ome')->model('branch');
        $branch_arr = $branchObj->getList('branch_id,branch_bn,name', $filter, $page_no, $page_size);
        return $branch_arr;
    }

    /**
     * 日志
     *
     * @return void
     * @author 
     **/
    public function _write_log($title, $original_bn, $status = 'success', $params = array(), $result=array(), $convert_params = array())
    {
        // 写日志
        $apilogModel = app::get('ome')->model('api_log');
        $log_id = $apilogModel->gen_id();

        if ($params['task'] && $result['rsp']=='succ') $apilogModel->set_repeat($params['task'],$log_id);

        $msg = '接收参数：' . var_export($params, true) . '<hr/>返回结果：' . var_export($result, true);
        $logsdf = array(
            'log_id'        => $log_id,
            'task_name'     => $title,
            'status'        => $status,
            'worker'        => '',
            'params'        => serialize(array($_REQUEST['method'], $_REQUEST)),
            'msg'           => $msg,
            'log_type'      => '',
            'api_type'      => 'response',
            'memo'          => '',
            'original_bn'   => $original_bn,
            'createtime'    => time(),
            'last_modified' => time(),
        );

        $apilogModel->insert($logsdf);

        
    }
}