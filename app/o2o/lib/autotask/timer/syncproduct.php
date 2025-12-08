<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class o2o_autotask_timer_syncproduct
{
    

     /* 当前的执行时间 */
    public static $now;
    
    /* 执行的间隔时间 */
    const intervalTime = 1800;
    
    function __construct()
    {
        self::$now = time();
    }

    /**
     * 处理
     * @param mixed $params 参数
     * @param mixed $error_msg error_msg
     * @return mixed 返回值
     */
    public function process($params, &$error_msg = '')
    {
        $servers = $this->geto2oserver();

        if(!$servers) return true;
        
        base_kvstore::instance('pos/sync/product')->fetch('sync-lastexectime',$lastExecTime);
        if (!$lastExecTime) {
            $lastExecTime = strtotime('-100 days');
        }
         
        base_kvstore::instance('pos/sync/product')->store('sync-lastexectime', self::$now);

        $data = array(
            'start_date'    =>  date('Y-m-d H:i:s',$lastExecTime),
            'end_date'      =>  date('Y-m-d H:i:s',self::$now),
        );


        $this->getMaterials($data);
        
        return true;
    }

    
    
    /**
     * 获取Materials
     * @param mixed $params 参数
     * @return mixed 返回结果
     */
    public function getMaterials($params)
    {
        
        $start_date = strtotime($params['start_date']);
        $end_date = strtotime($params['end_date']);
        $basicMaterialSelect = kernel::single('material_basic_select');

        $offset = 0;
        $limit  = 200;
        do {

            $materiallist    = $basicMaterialSelect->getlist('bm_id, material_bn, material_name,type', array('is_o2o_sales'=>1,'last_modified|between'=>array($start_date,$end_date)), $offset,$limit);

            if (!$materiallist) {
                break;
            }
            
            $data = array();
            foreach($materiallist as $value){
                $data[] = array(
                    'material_bn'       => $value['bn'],
                    'bm_id'             => $value['product_id'],
                    'type'              => $value['type'],
                );

            }
           
            if ($data) app::get('o2o')->model('syncproduct')->replaceinsert($data);
            $offset += $limit;

        } while (true);
        
    }

    /**
     * 获取o2oserver
     * @return mixed 返回结果
     */
    public function geto2oserver(){
        $serverMdl = app::get('o2o')->model('server');
        $servers =  $serverMdl->dump(array('type'=>'openapi'),'server_id');
        
        if($servers){
            return true;
        }
        return false;
    }

}