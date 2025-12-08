<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class sales_mdl_delivery_order extends dbeav_model{

    //是否有导出配置
    var $has_export_cnf = true;

    //所用户信息
    static $__USERS = null;

    var $export_name = '发货销售单';

    public $filter_use_like = true;

    /**
     * 搜索Options
     * @return mixed 返回值
     */
    public function searchOptions()
    {
        $options = parent::searchOptions();
        $options['order_bn'] = '订单号';
        return $options;
    }

    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter, $tableAlias = null, $baseWhere = null)
    {
        if($filter['order_bn']) {
            $items = app::get('sales')->model('delivery_order_item')->getList('delivery_id',array('order_bn'=>$filter['order_bn']));
            $filter['delivery_id|in'] = ['-1'];
            foreach ($items as $key => $value) {
                if(in_array($value['delivery_id'],$filter['delivery_id|in'])) {
                    continue;
                }
                $filter['delivery_id|in'][] = $value['delivery_id'];
            }
            unset($filter['order_bn']);
        }
        return parent::_filter($filter, $tableAlias, $baseWhere);
    }
    
    function export_csv($data,$exportType = 1 ){
        $output = array();
         foreach( $data['title'] as $k => $val ){
                $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
            }
        echo implode("\n",$output);
    }

    /**
     * 获取exportdetail
     * @param mixed $fields fields
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @param mixed $has_title has_title
     * @return mixed 返回结果
     */
    public function getexportdetail($fields,$filter,$offset=0,$limit=1,$has_title=false)
    {
        $salesList    = array();

        $itemsObj = app::get('sales')->model('delivery_order_item');
        $items_arr = $itemsObj->getList('*',array('delivery_id'=>$filter['delivery_id']));

        $row_num = 1;
        if($items_arr){
            foreach ($items_arr as $key => $v) {
               
                $saleItemRow['发货单号']   = $v['delivery_bn'];
                $saleItemRow['订单号']     = $v['order_bn'];
                $saleItemRow['基础物料编码']    = mb_convert_encoding($v['bn'], 'GBK', 'UTF-8') . "\t";
                $saleItemRow['基础物料名称']    = mb_convert_encoding($v['name'], 'GBK', 'UTF-8');
                $saleItemRow['关联销售物料编码']    = mb_convert_encoding($v['sales_material_bn'], 'GBK', 'UTF-8') . "\t";
                $saleItemRow['吊牌价']   = $v['price'];
                $saleItemRow['货品优惠']   = $v['pmt_price'];
                $saleItemRow['数量']   = $v['nums'];
                $saleItemRow['销售总价']   = $v['sale_price'];
                $saleItemRow['平摊优惠']   = $v['apportion_pmt'];
                $saleItemRow['销售金额']   = $v['sales_amount'];
                $data[$row_num] = implode(',', $saleItemRow);
                $row_num++;
            }
        }

        //明细标题处理
        if($data && $has_title){
            $title = array(
                '发货单号',
                '订单号',
                '基础物料编码',
                '基础物料名称',
                '关联销售物料编码',
                '吊牌价',
                '货品优惠',
                '数量',
                '销售总价',
                '平摊优惠',
                '销售金额',
            );

            foreach ((array)$title as $key => $value) {
                $title[$key] = mb_convert_encoding($value, 'GBK', 'UTF-8');
            }

            $data[0] = implode(',', $title);
        }

        ksort($data);
        return $data;
    }
    
    /**
     * modifier_ship_name
     * @param mixed $ship_name ship_name
     * @param mixed $list list
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_ship_name($ship_name,$list,$row)
    {
        if ($this->is_export_data) {
            if ('false' != app::get('ome')->getConf('ome.sensitive.exportdata.encrypt')) {
                return kernel::single('ome_view_helper2')->modifier_ciphertext($ship_name,'delivery','ship_name');
            }
            return $ship_name;
        }
        
        $is_encrypt = kernel::single('ome_security_hash')->check_encrypt($ship_name);
        
        if (!$is_encrypt) return $ship_name;
        
        $base_url = kernel::base_url(1);$delivery_id = $row['delivery_id'];
        $encryptShipName = kernel::single('ome_view_helper2')->modifier_ciphertext($ship_name,'delivery','ship_name');
        $return =<<<HTML
<a class="data-hide" href="javascript:void(0);" onclick="Ex_Loader('security',function(){new Security({url:'index.php?app=console&ctl=admin_delivery&act=showSensitiveData&p[0]={$delivery_id}',clickElement:\$(event.target)}).desHtml(\$(event.target).getNext()); });"></a><span><span sensitive-field="ship_name">{$encryptShipName}</span></span>
HTML;
        return $ship_name?$return:$ship_name;
    }
    
    /**
     * modifier_ship_mobile
     * @param mixed $mobile mobile
     * @param mixed $list list
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_ship_mobile($mobile,$list,$row)
    {
        if ($this->is_export_data) {
            if ('false' != app::get('ome')->getConf('ome.sensitive.exportdata.encrypt')) {
                return kernel::single('ome_view_helper2')->modifier_ciphertext($mobile,'delivery','ship_mobile');
            }
            return $mobile;
        }
        
        $is_encrypt = kernel::single('ome_security_hash')->check_encrypt($mobile);
        
        if (!$is_encrypt) return $mobile;
        
        $base_url = kernel::base_url(1);$delivery_id = $row['delivery_id'];
        $encryptMobile = kernel::single('ome_view_helper2')->modifier_ciphertext($mobile,'delivery','ship_mobile');
        $return =<<<HTML
<a class="data-hide" href="javascript:void(0);" onclick="Ex_Loader('security',function(){new Security({url:'index.php?app=console&ctl=admin_delivery&act=showSensitiveData&p[0]={$delivery_id}',clickElement:\$(event.target)}).desHtml(\$(event.target).getNext()); });"></a><span><span sensitive-field="ship_mobile">{$encryptMobile}</span></span>
HTML;
        return $mobile?$return:$mobile;
    }
    
    /**
     * modifier_ship_addr
     * @param mixed $ship_addr ship_addr
     * @param mixed $list list
     * @param mixed $row row
     * @return mixed 返回值
     */
    public function modifier_ship_addr($ship_addr,$list,$row)
    {
        if ($this->is_export_data) {
            if ('false' != app::get('ome')->getConf('ome.sensitive.exportdata.encrypt')) {
                return kernel::single('ome_view_helper2')->modifier_ciphertext($ship_addr,'delivery','ship_addr');
            }
            return $ship_addr;
        }
        
        $is_encrypt = kernel::single('ome_security_hash')->check_encrypt($ship_addr);
        
        if (!$is_encrypt) return $ship_addr;
        
        $base_url = kernel::base_url(1);$delivery_id = $row['delivery_id'];
        $encryptAddr = kernel::single('ome_view_helper2')->modifier_ciphertext($ship_addr,'delivery','ship_addr');
        $return =<<<HTML
<a class="data-hide" href="javascript:void(0);" onclick="Ex_Loader('security',function(){new Security({url:'index.php?app=console&ctl=admin_delivery&act=showSensitiveData&p[0]={$delivery_id}',clickElement:\$(event.target)}).desHtml(\$(event.target).getNext()); });"></a><span><span sensitive-field="ship_addr">{$encryptAddr}</span></span>
HTML;
        return $ship_addr?$return:$ship_addr;
    }
    

}
