<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 退换货实际处理类
 * 20170516
 * wangjianjun@shopex.cn
 */
class wap_receipt_reship{

    /**
     * 
     * 退换货单创建方法
     * @param array $data 发货通知单数据信息
     */

    public function create($sdf,&$msg = ''){
        //校验传入参数
        if (!$sdf["reship_bn"]){
            $msg = '缺少必要参数';
            return false;
        }
        //检查是否存在oms端退换货单对应的wap端待处理状态的退换货单 
        $mdl_wap_return = app::get('wap')->model('return');
        $rs_wap = $mdl_wap_return->dump(array("original_reship_bn"=>$sdf["reship_bn"],"status"=>1));
        if (!empty($rs_wap)){
            $msg = '此oms退换货单号已有对应wap端待处理状态的退换货单';
            return false;
        }
        //获取原始的退换货主表和明细表数据
        $mdl_ome_reship = app::get('ome')->model('reship');
        $mdl_ome_reship_items = app::get('ome')->model('reship_items');
        $rs_ome_reship = $mdl_ome_reship->dump(array("reship_bn"=>$sdf["reship_bn"]));
        $rs_ome_reship_items = $mdl_ome_reship_items->getList("*",array("reship_id"=>$rs_ome_reship["reship_id"]));
        if (empty($rs_ome_reship) || empty($rs_ome_reship_items)){
            $msg = '原始退换货单数据异常';
            return false;
        }
        //组主表和明细表数据
        $insert_arr_return = array(
            "return_bn" => $this->gen_id(),
            "original_reship_bn" => $rs_ome_reship["reship_bn"],
            "order_id" => $rs_ome_reship["order_id"],
            "shop_id" => $rs_ome_reship["shop_id"],
            "aftersale_id" => $rs_ome_reship["return_id"],
            "branch_id" => $rs_ome_reship["branch_id"],
            "changebranch_id" => $rs_ome_reship["changebranch_id"],
            "return_type" => $rs_ome_reship["return_type"],
            "ship_name" => $rs_ome_reship["ship_name"],
            "ship_addr" => $rs_ome_reship["ship_addr"],
            "ship_area" => $rs_ome_reship["ship_area"],
            "ship_zip" => $rs_ome_reship["ship_zip"],
            "ship_tel" => $rs_ome_reship["ship_tel"],
            "ship_mobile" => $rs_ome_reship["ship_mobile"],
            "ship_email" => $rs_ome_reship["ship_email"],
            "createtime" => time(),
            "tmoney" => $rs_ome_reship["tmoney"],
            "bmoney" => $rs_ome_reship["bmoney"],
            "diff_money" => $rs_ome_reship["diff_money"],
            "cost_freight" => $rs_ome_reship["cost_freight_money"],
            "bcmoney" => $rs_ome_reship["bcmoney"],
            "change_money" => $rs_ome_reship["change_amount"],
            "total_amount" => $rs_ome_reship["totalmoney"],
            'shop_type'=>$rs_ome_reship["shop_type"],
        );

        $this->_dealEncryptData($insert_arr_return);

      
        //同时插入主表和明细表数据
        $rs_wap_return_insert = $mdl_wap_return->insert($insert_arr_return);
        $insert_return_id = $mdl_wap_return->db->lastInsertId();
        if ($rs_wap_return_insert && $insert_return_id){
            $mdl_wap_return_items = app::get('wap')->model('return_items');
            foreach($rs_ome_reship_items as $var_o_r_i){
                $insert_arr_return_items = array(
                    "return_id" => $insert_return_id,
                    "product_id" => $var_o_r_i["product_id"],
                    "bn" => $var_o_r_i["bn"],
                    "name" => $var_o_r_i["product_name"],
                    "return_type" => $var_o_r_i["return_type"],
                    "num" => $var_o_r_i["num"],
                    "price" => $var_o_r_i["price"],
                );
                $mdl_wap_return_items->insert($insert_arr_return_items);
            }
            $msg = 'wap端退换货单新增成功';
            return true;
        }else{
            $msg = 'wap端退换货单主表信息新增失败';
            return false;
        }
    }
    
    //生成wap端退换货单号
    function gen_id(){
        $mdl_wap_return = app::get('wap')->model('return');
        $i = rand(0,9999);
        do{
            if(9999==$i){
                $i=0;
            }
            $i++;
            $return_bn = date("YmdH").'13'.str_pad($i,6,'0',STR_PAD_LEFT);
            $row = $mdl_wap_return->dump(array("return_bn"=>$return_bn));
        }while($row);
        return $return_bn;
    }

    protected function _dealEncryptData(&$sdf) {
        $shop_type = $sdf['shop_type'] == 'tmall' ? 'taobao' : $sdf['shop_type'];
        if (kernel::single('ome_security_router', $shop_type)->is_encrypt($sdf, 'reship')) {
            //print_r($sdf);
            foreach ($sdf as $key => $string) {
                if(is_string($string)) {
                    $is_encrypt = kernel::single('ome_security_hash')->check_encrypt($string);
            
                    if ($is_encrypt) {
                        if($index = strpos($string, '>>')) {
                            $sdf[$key] = substr($string, 0, $index);
                            continue;
                        }

                        if($index = strpos($string, '@hash')) {
                            $sdf[$key] = substr($string, 0, $index);
                            continue;
                        }

                        if($index = strpos($string, '&gt;&gt;')) {
                            $sdf[$key] = substr($string, 0, $index);
                            continue;
                        }
                    }

                }
            }
        }
    }


    /**
     * cancel
     * @param mixed $sdf sdf
     * @param mixed $msg msg
     * @return mixed 返回值
     */
    public function cancel($sdf,&$msg = ''){
      
        $returnMdl = app::get('wap')->model('return');

        $returns = $returnMdl->dump(array("original_reship_bn"=>$sdf["reship_bn"]),"return_id,return_bn,status");
        
        if($returns['status']=='1'){
            //更新门店退换单为已拒绝状态 
            $returnMdl->update(array("status"=>2),array("return_id"=>$returns["return_id"]));
            $msg = '已拒绝成功';
            return true;
            
        }else{
            $msg = '已完成或已取消';
            return true;
            
        }
        
       
        
    }
    
}