<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class vop_autotask_timer_bill {
    /* 执行的间隔时间 */
    const intervalTime = 21600;
    #脚本执行时间
    const loopTime = 900;
    /* 当前的执行时间 */
    public static $now;
    
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
    public function process($params, &$error_msg='')
    {
        @set_time_limit(0);
        @ini_set('memory_limit','128M');
        ignore_user_abort(1);

       
        base_kvstore::instance('console/vop/bill')->fetch('apply-lastexectime',$lastExecTime);
        if($lastExecTime && ($lastExecTime+self::intervalTime)>self::$now) {
            $error_msg = '上次执行时间为'.date('Y-m-d H:i:s',$lastExecTime);
            return false;
        }
        
        $lastExecTime = $lastExecTime ? : (time()-7*86400);
       
        base_kvstore::instance('console/vop/bill')->store('apply-lastexectime', self::$now);
        //获取唯品会店铺
        $shopObj    = app::get('ome')->model('shop');
        $shopList   = $shopObj->getList('shop_id, shop_bn, name,config', array('node_type'=>'vop', 'node_id|noequal'=>'', 'tbbusiness_type'=>'jit'));

        
        if(empty($shopList))
        {
            return false;
        }
        
        $download_vop_bill_flag = false;
        foreach ($shopList as $key => $shop_info)
        {

            $config = is_string($shop_info['config']) ? @unserialize($shop_info['config']) : $shop_info['config'];

            if (!is_array($config) || $config['download_vop_bill'] != 'yes') {
                continue;
            }

            kernel::single('vop_bill')->getBillNumber($lastExecTime, self::$now, $shop_info['shop_id']);
            $download_vop_bill_flag = true;
        }

        if($download_vop_bill_flag){
            $this->getBillDetail();

            $this->getBillDiscountDetail();

            $this->getItemSourceDetail();
        }
        

    }

    /**
     * 获取BillDetail
     * @return mixed 返回结果
     */
    public function getBillDetail() {
        do {
            $model = app::get('vop')->model('bill');
            $filter = [
                'status' => '0',
                'sync_status' => '0',
                'create_time|than' => strtotime('-7 days')
            ];

            $oldRow = $model->db_dump($filter);
            
            if(!$oldRow) {
                $filter = [
                    'status' => '0',
                    'sync_status' => '1',
                    'last_modified|lthan' => (time() - 600),
                ];

                $oldRow = $model->db_dump($filter);

              
                if($oldRow) {
                    $model->update(['sync_status'=>'0'], ['id'=>$oldRow['id'], 'status'=>'0']);
                } else {
                    break;
                }
            }
          
            if($oldRow) {

                kernel::single('vop_bill')->getBillDetail($oldRow);
                
            }
            if(time() > self::$now + self::loopTime) {
                break;
            }
        } while(true);
    }

    /**
     * 获取BillDiscountDetail
     * @return mixed 返回结果
     */
    public function getBillDiscountDetail() {
        do {
            $model = app::get('vop')->model('bill');
            $filter = [
                'status' => '0',
                'discount_sync_status' => '0',
                'create_time|than' => strtotime('-7 days')
            ];
            $oldRow = $model->db_dump($filter);
            if(!$oldRow) {
                $filter = [
                    'status' => '0',
                    'discount_sync_status' => '1',
                    'last_modified|lthan' => (time() - 600)
                ];
                $oldRow = $model->db_dump($filter);
                if($oldRow) {
                    $model->update(['discount_sync_status'=>'0'], ['id'=>$oldRow['id'], 'status'=>'0']);
                } else {
                    break;
                }
            }
            if($oldRow) {
                kernel::single('vop_bill')->getBillDiscountDetail($oldRow);
                
            }
            if(time() > self::$now + self::loopTime) {
                break;
            }
        } while(true);
    }

    /**
     * 获取ItemSourceDetail
     * @return mixed 返回结果
     */
    public function getItemSourceDetail() {
        do {
            $model = app::get('vop')->model('bill');
            $filter = [
                'status' => '0',
                'detail_sync_status' => '0',
                'create_time|than' => strtotime('-7 days')
            ];
            $oldRow = $model->db_dump($filter);
            if(!$oldRow) {
                $filter = [
                    'status' => '0',
                    'detail_sync_status' => '1',
                    'last_modified|lthan' => (time() - 600)
                ];
                $oldRow = $model->db_dump($filter);
                if($oldRow) {
                    $model->update(['detail_sync_status'=>'0'], ['id'=>$oldRow['id'], 'status'=>'0']);
                } else {
                    break;
                }
            }
            if($oldRow) {
                kernel::single('vop_bill')->getItemSourceDetail($oldRow);
                
            }
            if(time() > self::$now + self::loopTime) {
                break;
            }
        } while(true);
    }
}   