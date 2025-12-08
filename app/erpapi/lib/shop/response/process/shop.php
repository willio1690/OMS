<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 平台通知OMS业务
 *
 * @author wangbiao<wangbiao@shopex.cn>
 * @version 2023.01.05
 */
class erpapi_shop_response_process_shop
{
    /**
     * 翱象系统通知签约信息给到OMS
     *
     * @param array $params
     * @return array
     */
    public function aoxiang_signed($params)
    {
        $shopMdl = app::get('ome')->model('shop');
        $queueMdl = app::get('base')->model('queue');
        
        //data
        $shop_id = $params['shop_id'];
        $signed_type = strtolower($params['signed_type']); //签约模式(sign签约、cancel取消签约)
        $signed_time = ($params['bizRequestTime'] ? $params['bizRequestTime'] : time());
        
        //店铺信息
        $shopInfo = $shopMdl->dump(array('shop_id'=>$shop_id), '*');
        
        //check
        if(empty($shopInfo)){
            return array('rsp'=>'fail', 'msg'=>'店铺数据ERP无法找到', 'msg_code'=>'AX_CHECK_0001');
        }
        
        if(!in_array($shopInfo['shop_type'], array('taobao', 'tmall'))){
            return array('rsp'=>'fail', 'msg'=>'店铺不是淘宝类型,禁止操作', 'msg_code'=>'AX_CHECK_0001');
        }
        
        //update
        $msg = '';
        $is_auto = false;
        switch ($signed_type)
        {
            case 'sign':
                $msg = '店铺签约成功';
                
                if($shopInfo['aoxiang_signed'] == '1'){
                    $msg = '店铺已经是签约状态,请不要重复通知';
                    break;
                }
                
                //update
                $shopMdl->update(array('aoxiang_signed'=>'1', 'aoxiang_signed_time'=>$signed_time), array('shop_id'=>$shop_id));
                
                //自动分配任务标识
                $is_auto = true;
                
                break;
            case 'cancel':
                $msg = '取消签约成功';
                
                if($shopInfo['aoxiang_signed'] == '2'){
                    $msg = '店铺已经是取消签约状态,请不要重复通知';
                    break;
                }
                
                //update
                $shopMdl->update(array('aoxiang_signed'=>'2', 'aoxiang_signed_time'=>$signed_time), array('shop_id'=>$shop_id));
                
                break;
            default:
                return array('rsp'=>'fail', 'msg'=>'无效的签约方式');
        }
        
        //自动分配任务
        if($is_auto){
            $aoxiangLib = kernel::single('dchain_aoxiang');
        
            //get config
            $aoxiangConfig = $aoxiangLib->getAoxiangSyncConfig($shop_id);
        
            //仓库自动分配队列任务
            if($aoxiangConfig['sync_branch'] != 'false'){
                $queueData = array(
                    'queue_title' => '仓库自动分配翱象队列任务',
                    'start_time' => time(),
                    'params' => array(
                        'sdfdata' => array('shop_id'=>$shop_id),
                        'app' => 'dchain',
                        'mdl' => 'aoxiang_branch',
                    ),
                    'worker'=> 'dchain_aoxiang.autoTaskAddBranch',
                );
                $queueMdl->save($queueData);
            }
        
            //物流公司自动分配队列任务
            if($aoxiangConfig['sync_logistics'] != 'false') {
                $queueData = array(
                    'queue_title' => '物流公司自动分配翱象队列任务',
                    'start_time' => time(),
                    'params' => array(
                        'sdfdata' => array('shop_id' => $shop_id),
                        'app' => 'dchain',
                        'mdl' => 'aoxiang_logistics',
                    ),
                    'worker' => 'dchain_aoxiang.autoTaskAddLogistics',
                );
                $queueMdl->save($queueData);
            }
        
            //所有商品自动分配队列任务
            if($aoxiangConfig['sync_product'] != 'false') {
                $queueData = array(
                    'queue_title' => '所有商品自动分配队列任务',
                    'start_time' => time(),
                    'params' => array(
                        'sdfdata' => array('shop_id' => $shop_id),
                        'app' => 'dchain',
                        'mdl' => 'aoxiang_product',
                    ),
                    'worker' => 'dchain_inventorydepth.autoTimerProduct',
                );
                $queueMdl->save($queueData);
            }
        }
    
        return array('rsp'=>'succ', 'msg'=>$msg);
    }
}