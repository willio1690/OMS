<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_event_trigger_exrecommend_data_recommend_common {
    protected $__sdf = array();
    protected  $__channel_type = '';
    public $__seller_id = '';#查明这个值是在哪里弄进去的
    public $__order_info = array();
    public $__main_order_info = array();


    public function init($channel_info,$branch_id=''){
        $this->__sdf = array();
        $this->__seller_id = $channel_info['tb_seller_id'];
        $this->__channel_type = $channel_info['channel_type'];
        $this->__branch_id = $branch_id;
        $this->set_sender_info($channel_info);
        return $this;
    }
    #收货方信息
    public function set_recipient_info($order_params){
    }
    #发货方信息
    public function set_sender_info($channel_info){
     
         $this->__sdf['sender_info'] = array(
            'address'=> array(
                'province'=>$channel_info['province'],
                'city'=>$channel_info['city'],
                'district'=>$channel_info['area'],
                'town'=>'',
                'detail'=>$channel_info['address_detail'],
            ),
            'mobile'=>$channel_info['mobile'],
            'name'=>$channel_info['default_sender'],#发货人
            'phone'=>$channel_info['tel'],
        );
        return true;
    }
    public function getExrecommendSdf($order_data) {
        $all_order_ids = $order_data['combine_order_ids'];
        $this->__order_info = app::get('ome')->model('orders')->getList('order_id,order_bn,custom_mark,mark_text,shop_type,ship_name,ship_area,ship_addr,ship_mobile,ship_tel,process_status,logi_no,shop_id',array('order_id|in'=>$all_order_ids)); 
        return $this->__sdf;
    }
    #订单信息
    public function get_order_info(&$order_info){
        foreach($order_info as $v){
            $order_list['buyer_message'] = '';#可选
            $order_list['seller_memo'] ='';#可选
            $order_list['trade_order_id'] =$v['order_bn'];
            $list_order_info[] = $order_list;
        }
        return $list_order_info;
    }
    #包裹明细
    public function get_package_info($order_ids){
        $obj_order_items = app::get('ome')->model('order_items');
        $obj_order_objects = app::get('ome')->model('order_objects');
         
        $_item_info = $obj_order_items->getList('obj_id,item_type,bn,name,nums,`delete`',array('order_id|in'=>$order_ids));
        $delete_pkg_object_ids = array();
        $product_items  = $item_info = array();
        foreach($_item_info as $v){
            $bn = strtoupper($v['bn']);
            #需要把捆绑商品中已删除的捆绑商品找出来(捆绑商品是整体性删除)
            if($v['item_type'] == 'pkg' && $v['delete'] == 'true'){
                $delete_pkg_object_ids[$v['obj_id']] = $v['obj_id'];
            }elseif($v['item_type'] != 'pkg' && $v['delete'] =='false'){
                if($item_info[$bn]){
                    $item_info[$bn]['count'] = $item_info[$bn]['count'] + $v['nums'];
                }else{
                    $item_info[$bn] =  array('code'=>$v['bn'],'name'=>$v['name'],'count'=>$v['nums']);
                }
            }
        }
        $object_filter = array('order_id|in'=>$order_ids,'obj_type'=>'pkg');
        if($delete_pkg_object_ids){
            $object_filter['filter_sql'] = ' obj_id not in ( '.implode(',',$delete_pkg_object_ids).' ) ';
        }
        $_object_info = $obj_order_objects->getList('bn as code, name,quantity as count',$object_filter);
        $object_infos = array();
        foreach($_object_info as $v){
            $bn = strtoupper($v['code']);
            if($object_infos[$bn]){
                $object_infos[$bn]['count'] = $object_infos[$bn]['count']+$v['count'];
            }else{
                $object_infos[$bn] = $v;
            }
        }
        if($item_info){
            $order_item_info = array_merge($object_infos,$item_info);
        }else{
            $order_item_info = $object_infos;
        }
        $items = array_values( $order_item_info);
        return $items;
    }
    #收货方信息(合并订单，使用主单的收货方信息)
    public function get_recipient_info(){
        $main_order_info = $this->__main_order_info;
        preg_match("/:(.*):/", $main_order_info['ship_area'],$tmp_area);
        if($tmp_area[1]){
            $ship_area = explode('/', $tmp_area[1]);
        }
        return array(
            'address'=>array(
                'province'=>$ship_area[0],
                'city'=>$ship_area[1],
                'distric'=>$ship_area[2],
                'town'=> '',
                'detail'=>$main_order_info['ship_addr']
            ),
            'mobile'=>$main_order_info['ship_mobile'],
            'name'=>$main_order_info['ship_name'],
            'phone'=>$main_order_info['ship_tel'],
        );
    }
    public function set_main_order_info($main_order_info){
        $this->__main_order_info  =  $main_order_info;
        return true;
    }
    #智选物流提供方支持的物流公司,菜鸟支持的智能发货物流是：6大快递＋邮政
    public function support_logistics_code($source,$type){
        $all_code = array(
             #中通（ZTO），圆通(YTO)，申通(STO)，百世(HTKY)，韵达（YUNDA），天天（TTKDEX），邮政小包（POSTB），邮政标准快递（5000000007756），EMS（EMS），EMS经济快递（EYB）
            'taobao'=>array('ZTO'=>"ZTO",'YTO'=>"YTO",'STO'=>"STO",'HTKY'=>"HTKY",'YUNDA'=>"YUNDA",'TTKDEX'=>"TTKDEX",'POSTB'=>"POSTB",'5000000007756'=>"5000000007756",'EMS'=>"EMS",'EYB'=>"EYB"),
            'hqepay'=>kernel::single('channel_func')->support_logistics_code()
        );
        return $all_code[$this->__channel_type];
    }
    public function get_shop_type($shop_type){
        #淘宝(TB)、天猫(TM)、京东(JD)、当当(DD)、拍拍(PP)、易讯(YX)、ebay(EBAY)、QQ网购(QQ)、亚马逊(AMAZON)、苏宁(SN)、国美(GM)、唯品会(WPH)、聚美(JM)、乐蜂(LF)、蘑菇街(MGJ)、聚尚(JS)、拍鞋(PX)、银泰(YT)、1号店(YHD)、凡客(VANCL)、邮乐(YL)、优购(YG)、阿里 巴巴(1688)、其他(OTHERS)。
        $all_shop_type =  array (
            'taobao'     => 'TB',
            'tmall'      => 'TM',
            '360buy'     => 'JD',
            'dangdang'   => 'DD',
            'paipai'     => 'PP',
            'yihaodian'  => 'YHD',
            'qq_buy'     => 'QQ',
            'amazon'     => 'AMAZON',
            'suning'     => 'SN',
            'gome'       => 'GM',
            'vop'        =>'WPH',
            'mogujie'    => 'MGJ',
            'mgj'        =>'MGJ',
            'yintai'     => 'YT',
            'yihaodian'  => 'YHD',
            'vjia'       => 'VANCL',
            'alibaba'    => '1688',
        );
        if(!$all_shop_type[$shop_type]){
            return 'OTHERS';
        }
        return  $all_shop_type[$shop_type];
    }
    #获取策略仓设置参数
    public function getStrategySdf(){
        return true;
    }
}