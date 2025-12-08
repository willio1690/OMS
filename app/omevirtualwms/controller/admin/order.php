<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

define('WAIT_TIME',5);//finder中的数据多长时间重复展示(队列失败的情况)，单位 分
define('TIP_INFO','此数据已模拟发送，请注意是否要再次模拟发送');//再次展示时的提示信息
date_default_timezone_set('Etc/GMT-0');
class omevirtualwms_ctl_admin_order extends desktop_controller{

    /**
     * __construct
     * @param mixed $app app
     * @return mixed 返回值
     */
    public function __construct($app){
        parent::__construct($app);
        $this->app        = $app;
        $this->objBhc     = kernel::single('base_httpclient');
//         $thi->objBcf      = kernel::single('base_certificate');
        $api_url        = kernel::base_url(1).kernel::url_prefix().'/api';
        $certificate    = base_shopnode::node_id('ome');
        $token          = base_certificate::token();
        $this->api_url    = $api_url;//api地址
        $this->token      = $token;//证书token
    }

    /**
     * virtual_order
     * @return mixed 返回值
     */
    public function virtual_order(){
        $this->page('order/virtual_order_index.html');
    }


    //新建/编辑订单
    /**
     * virtual_order_edit
     * @param mixed $order_id ID
     * @return mixed 返回值
     */
    public function virtual_order_edit($order_id=0){
        
        $shopObj = app::get('ome')->model("shop");
        //这里过滤掉没有绑定的前端店铺
        $shopData = $shopObj->getList('*',array('node_id|noequal'=>NULL));
        $this->pagedata['shopData'] = $shopData;
        $this->pagedata['creatime'] = date("Y-m-d H:i:s",time());
        if($order_id){
            $orders_mdl = app::get('ome')->model('orders');
            $order_objects_mdl = app::get('ome')->model('order_objects');
            $ordersData = $orders_mdl->dump($order_id,"*",array("order_objects"=>array("*")));
            //优惠方案
            $oOrderPmt = app::get('ome')->model('order_pmt');
            $order_pmt = $oOrderPmt->getList('pmt_amount,pmt_describe',array('order_id'=>$order_id));
            $ordersData['pmt_detail'] = $order_pmt;

            //订单明细
            $order_objects = $ordersData['order_objects'];
            foreach($order_objects as &$v){
                //物料版获取订单相关商品信息 用objects层
                $order_items = $order_objects_mdl->getlist('*',array('obj_id'=>$v['obj_id']));
                foreach($order_items as $v1){
                    $v['order_items'][] = array(
                        'bn' => $v1['bn'],
                        'quantity' => $v1['quantity'],
                        'pmt_price' => $v1['pmt_price'],
                        'sale_price' => $v1['sale_price'],
                    );
                }
            }
            $ordersData['order_objects'] = $order_objects;


            //print_r($ordersData);exit;
            $this->pagedata['ordersData'] = $ordersData;
            //获取shop的node_id
            $this->pagedata['edit'] = true;
            $rs_selected_shop = $shopObj->dump(array("shop_id"=>$ordersData['shop_id']),'node_id');
            $this->pagedata['shop_node_id'] = $rs_selected_shop["node_id"];
        }
        $this->singlepage("order/virtualwms_edit_order.html");
    }


    /**
     * virtual_order_edit_save
     * @return mixed 返回值
     */
    public function virtual_order_edit_save()
    {
        $mdlSalesMaterial = app::get('material')->model('sales_material');
        $mdlSalesMaterialExt = app::get('material')->model('sales_material_ext');
        
        $sdf = $_POST['orders'];

        //收货人省市区
        if($sdf['consignee']['area']){
            $_area = explode(':',$sdf['consignee']['area']);
            $area = explode('/',$_area[1]);
            $sdf['consignee']['area_state'] = $area[0];
            $sdf['consignee']['area_city'] = $area[1];
            $sdf['consignee']['area_district'] = $area[2];
            unset($sdf['consignee']['area']);
        }

        //发货人省市区
        if($sdf['consigner']['area']){
            $_area = explode(':',$sdf['consigner']['area']);
            $area = explode('/',$_area[1]);
            $sdf['consigner']['area_state'] = $area[0];
            $sdf['consigner']['area_city'] = $area[1];
            $sdf['consigner']['area_district'] = $area[2];
            unset($sdf['consigner']['area']);
        }

        //优惠方案
        $_pmt_detail = $sdf['pmt_detail'];
        $pmt_detail = array();
        unset($sdf['pmt_detail']);
        if($_pmt_detail){
            foreach($_pmt_detail['pmt_amount'] as $k=>$v){
                $pmt_detail[$k] = array(
                    'pmt_amount' => $v,
                    'pmt_describe' => $_pmt_detail['pmt_describe'][$k],
                );
            }
        }
        $sdf['pmt_detail'] = json_encode($pmt_detail);

        //order_object
        $order_items = $sdf['order_items'];
        
        $order_objects = array();
        foreach($order_items['bn'] as $k=>$v)
        {
            $object = array();

            $product = $mdlSalesMaterial->getList('*',array('sales_material_bn'=>$v),0,1);
            $product_ext = $mdlSalesMaterialExt->getList('*',array('sm_id'=>$product[0]['sm_id']),0,1);
            
            $goods = $product;
            $object[$goods[0]['goods_id']]['obj_type'] ='goods';
            $object[$goods[0]['goods_id']]['obj_alias'] = '';
//             $object[$goods[0]['goods_id']]['bn'] = $goods[0]['bn'];
//             $object[$goods[0]['goods_id']]['oid'] = $goods[0]['bn'];
//             $object[$goods[0]['goods_id']]['name'] = $goods[0]['name'];
            $object[$goods[0]['goods_id']]['bn'] = $goods[0]['sales_material_bn'];
            $object[$goods[0]['goods_id']]['oid'] = $goods[0]['sales_material_bn'];
            $object[$goods[0]['goods_id']]['name'] = $goods[0]['sales_material_name'];
            $object[$goods[0]['goods_id']]['price'] = '';
            //$object[$goods[0]['goods_id']]['amount'] = '';
            $object[$goods[0]['goods_id']]['pmt_price'] = '';
            $object[$goods[0]['goods_id']]['sale_price'] = '';
            //$object[$goods[0]['goods_id']]['quantity'] = '';
            //$object[$goods[0]['goods_id']]['weight'] = '100';
            $object[$goods[0]['goods_id']]['score'] = '0';
            $spec_info = array();
            if($product[0]['spec_info']){
                $_spec_info = explode('、',$product[0]['spec_info']);
                foreach($_spec_info as &$v1){
                    $_si = explode('：',$v1);
                    $spec_info[] = array('label' => $_si[0],'value' => $_si[1]);
                }

            }
            $order_items_amount = $product_ext[0]['retail_price']*$order_items['nums'][$k];
            $order_items_quantity = $order_items['nums'][$k];
            $order_items_weight = $product_ext[0]['weight'];
            $order_items_price = $product_ext[0]['retail_price'];
            $order_items_pmt_price = $order_items['pmt_price'][$k];
            //获取销售物料信息
            
            $object[$goods[0]['goods_id']]['order_items'][] = array(
                'item_type' => 'product',
                'bn' => $v,
                'name' => $product[0]['sales_material_name'],
                'product_attr' => $spec_info,
                'quantity' => $order_items_quantity,
                'price' => $order_items_price,
                'amount' => $order_items_amount,
                'pmt_price' => $order_items_pmt_price,
                'sale_price' => $order_items['sale_price'][$k],
                'weight' => $order_items_weight,
                'status' => 'active',

            );

            if($object[$goods[0]['goods_id']]['obj_type'] == 'goods'){
                $object[$goods[0]['goods_id']]['amount'] += $order_items_amount;
                $object[$goods[0]['goods_id']]['quantity'] += $order_items_quantity;
                $object[$goods[0]['goods_id']]['weight'] += $order_items_weight;

            }elseif($object[$goods[0]['goods_id']]['obj_type'] == 'pkg'){ //不走这里的obj_type这里写死的 goods
            }
            
            $order_objects[] = $object[$goods[0]['goods_id']];
            
        }
        unset($sdf['order_items']);
        
        //计算支付手续费
        if($sdf['pay_status'] != 0 && $sdf['payments']){
            foreach($sdf['payment'] as $v){
                $sdf['payinfo']['cost_payment'] += $v['pay_cost'];
            }
        }else{
            $sdf['payinfo']['cost_payment'] = 0;
        }

        //计算订单总金额
        $sdf['total_amount'] = (float)$sdf['cost_item']+(float)$sdf['shipping']['cost_shipping']+(float)$sdf['cost_tax']+(float)$sdf['shipping']['cost_protect']+(float)$sdf['payinfo']['cost_payment']+(float)$sdf['discount']-(float)$sdf['pmt_goods']-(float)$sdf['pmt_order'];
        $sdf['cur_amount'] = $sdf['total_amount'];
    
        $sdf['order_objects'] = json_encode($order_objects);

        //配送信息
        $sdf['shipping'] = json_encode($sdf['shipping']);
        //支付信息
        $sdf['payinfo'] = json_encode($sdf['payinfo']);
        //收货人信息
        $sdf['consignee'] = json_encode($sdf['consignee']);
        //发货人信息
        $sdf['consigner'] = json_encode($sdf['consigner']);
        //代销人信息
        $sdf['selling_agent'] = json_encode($sdf['selling_agent']);
        //买家会员信息
        $sdf['member_info'] = json_encode($sdf['member_info']);
        //支付单(兼容老版本)
        $sdf['payment_detail'] = json_encode($sdf['payment_detail']);
        //支付单(新版本)
        if($sdf['pay_status'] == 0){
            $sdf['payments'] = '';
        }else{
            $sdf['payments'] = json_encode($sdf['payments']);
        }

        //判断是否选择了分销b2b的店铺选项
        if($sdf['shop_b2b_select']){
            //自由体系分销王选择走分销
            if($sdf['shop_b2b_select'] == 'spfx'){
                $sdf['t_type'] = 'fenxiao';
            }
            //属于淘分销的 代销和经销
            if(in_array($sdf['shop_b2b_select'],array('tbdx','tbjx'))){
                $sdf['t_type'] = 'fenxiao';
                $sdf['fx_order_id'] = $sdf['order_bn'];
                $sdf['order_source'] = $sdf['shop_b2b_select'];
            }
        }
         
//         error_log(print_r($sdf,1),3,'d:/ordersdf.txt');
        $rs = $this->order_request($sdf);
        print_r($rs);
    }

    //获取店铺相关有分销b2b的下拉框选择 分销王（分销 ） 淘分销 （淘宝代销、淘宝分销）
    function ajax_load_fx_select(){
        $shop_node_id = $_POST['shop_node_id'];
        if(!$shop_node_id){
            return false;
        }
        $option_value = $_POST['option_value'];
    
        $arr_b2b_select = array();
        $mdlOmeShop = app::get('ome')->model('shop');
        $rs_shop = $mdlOmeShop->dump(array('node_id'=>$shop_node_id),'node_type,business_type');
        if(empty($rs_shop)){
            return false;
        }
    
        if($rs_shop['node_type'] == 'shopex_b2b'){
//             $arr_b2b_select = array('spzx'=>'直销','spfx'=>'分销');
            $arr_b2b_select = array('spfx'=>'分销');//分销王走分销b2b
        }
        if($rs_shop['node_type'] == 'taobao' && $rs_shop['business_type'] == 'fx'){
            $arr_b2b_select = array('tbdx'=>'淘宝代销','tbjx'=>'淘宝经销');
        }
    
        if(empty($arr_b2b_select)){
            return false;
        }
    
        $shop_b2b_html = "<select name='orders[shop_b2b_select]'>";
        foreach ($arr_b2b_select as $key_value => $var_text){
            $selected = "";
            if($option_value && $key_value == $option_value){
                $selected = "selected";
            }
            $shop_b2b_html .= "<option ".$selected." value='".$key_value."'>".$var_text."</option>";
        }
        $shop_b2b_html .= "</select>";
    
        echo $shop_b2b_html;
        exit;
    }
    
    /**
     * order_request
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */
    public function order_request($sdf){
        $method = 'ome.order.add';
        $node_id = $sdf['node_id'];
        unset($sdf['node_id']);
        $params = $sdf;
        $query_params = array(
            'method'=>$method,
            'date'=>'',
            'format'=>'json',
            'node_id'=> $node_id,
            'app_id' => 'ecos.ome',
        );
        $query_params = array_merge((array)$params,$query_params);
        $query_params['sign'] = $this->_gen_sign($query_params,$this->token);

        $rs = $this->objBhc->post($this->api_url,$query_params);
        return $rs;
    }


     private function _assemble($params){
        if(!is_array($params))  return null;
        ksort($params, SORT_STRING);
        $sign = '';
        foreach($params as $key=>$val){
            if(is_null($val))   continue;
            if(is_bool($val))   $val = ($val) ? 1 : 0;
            $sign .= $key . (is_array($val) ? self::_assemble($val) : $val);
        }
        return $sign;
    }

    private function _gen_sign($params,$token){
        return strtoupper(md5(strtoupper(md5($this->_assemble($params))).$token));
    }

    /**
     * 获取_order_id
     * @param mixed $order_bn order_bn
     * @return mixed 返回结果
     */
    public function get_order_id($order_bn){
        $oOrder = app::get('ome')->model('orders');
        $order = $oOrder->getlist('order_id',array('order_bn'=>$order_bn),0,-1);
        print_r(json_encode($order[0]));
        exit;
    }
}