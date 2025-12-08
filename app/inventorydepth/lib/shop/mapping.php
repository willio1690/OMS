<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 *
 */
class inventorydepth_shop_mapping extends eccommon_analysis_abstract implements eccommon_analysis_interface
{
    public $type_options = array(
        'display' => 'true',
    );
    
    /**
     * 将字符串做crc32
     *
     * @return void
     * @author
     **/
    public function crc32($val)
    {
        return sprintf('%u', crc32($val));
    }
    
    /**
     * 下载抖音商品
     * @param $params
     * @param $page
     * @return string
     */
    public function downloadAllGoods($params, $page)
    {
        $shop_id = $params['shop_id'];
        if (empty($shop_id)) {
            return $rs['err_msg'][] = '店铺不能为空';
        }
        $down['page']       = $page;
        $down['goods_type'] = $params['goods_type'];
        
        //开始时间(年-月-日)
        if($params['start_time']){
            $down['start_time'] = strtotime($params['start_time'].' 00:00:00');
        }
        
        //request
        $result = kernel::single('erpapi_router_request')->set('shop', $shop_id)->product_itemsListGet($down);
        if ($result['rsp'] != 'succ') {
            return $result;
        }
        $data                     = $result['data'];
        $data['items_error_nums'] = 0;
        $data['rsp']              = $result['rsp'];
        $items                    = $result['data']['data'];
        
        //获取商品总数
        $page_num = ($data['page'] ? $data['page'] : 1);
        $page_size = ($data['size'] ? $data['size'] : 50);
        $total = intval($data['total']);
        
        //是否有下一页数据
        $data['current_page'] = $page_num; //当前页码
        $data['all_pages'] = ceil($total / $page_size); //总页码
        
        //店铺信息
        $shopObj   = app::get('ome')->model('shop');
        $shop_info = $shopObj->dump(['shop_id' => $shop_id], 'shop_id,shop_bn,name,shop_type,cos_id');
        //管理员信息
        $ad_info = kernel::single('ome_func')->getDesktopUser();
        //物料信息
        $materialObj     = app::get('material')->model('basic_material');
        $material_bn_arr = array_filter(array_column($items, 'outer_product_id'));
        $material_list   = array();
        if (!empty($material_bn_arr)) {
            $material_list = $materialObj->getList('material_bn', ['material_bn|in' => $material_bn_arr]);
            $material_list = array_column($material_list, null, 'material_bn');
        }
        $inventorydepthItemsObj = app::get('inventorydepth')->model('shop_items');
        $inventorydepthSkusObj  = app::get('inventorydepth')->model('shop_skus');
        $data['succ']           = $data['fail'] = 0;
        foreach ($items as $key => $value) {
            $data['succ']                  += 1;
            $bn                            = isset($material_list[$value['outer_product_id']]) ? $material_list[$value['outer_product_id']]['material_bn'] : '';
            $items[$key]['bn']             = $bn;
            $items[$key]['outer_id']       = $bn;
            $items[$key]['sku_id']         = $bn;
            $items[$key]['approve_status'] = $value['sku_status'] == 0 ? 'onsale' : 'instock';
            $items[$key]['download_time']  = time();
            $items[$key]['created']        = time();
            $items[$key]['modified']       = time();
            $items_rs                      = $inventorydepthItemsObj->isave($items[$key], $shop_info);
            if (!$items_rs) {
                $data['fail'] += 1;
            }
            
            
            $detail_result = kernel::single('erpapi_router_request')->set('shop',
                $shop_id)->product_itemsGet($value['iid']);
            if ($detail_result['rsp'] == 'succ') {
                //插入sku数据
                $sku_data = array();
                foreach ($detail_result['data'] as $k => $v) {
                    $sku_data[$k]['iid']        = $items[$key]['iid'];
                    $sku_data[$k]['outer_id']   = $v['code'];
                    $sku_data[$k]['sku_id']     = $v['sku_id'];
                    $sku_data[$k]['price']      = bcdiv($v['discount_price'], 100);
                    $sku_data[$k]['shop_stock'] = $v['stock_num'];
                    $sku_data[$k]['op_id']      = $ad_info['op_id'];
                    $sku_data[$k]['op_name']    = $ad_info['op_name'];
                }
                $inventorydepthSkusObj->isave(['sku' => $sku_data], $shop_info, $items[$key]);
            }
        }
        
        $data['next_page'] = $data['current_page'] + 1;
        
        if ($data['current_page'] == $data['all_pages']) {
            $data['next_page'] = 0;
        }
        return $data;
    }
    
    public function get_type()
    {
        $return = array(
            'system_name'     => array(
                'lab'  => '商品名称',
                'type' => 'text',
            ),
            'shop_iid'     => array(
                'lab'  => '平台商品ID',
                'type' => 'text',
            ),
            'shop_product_bn' => array(
                'lab'  => '系统商品ID',
                'type' => 'text',
            ),
        );
        return $return;
    }
    
    public function headers()
    {
        $this->_render->pagedata['title'] = $this->_title;
        
        if ($this->type_options['display'] == 'true') {
            $this->_render->pagedata['type_display']  = 'true';
            $this->_render->pagedata['typeData']      = $this->get_type();
            $this->_render->pagedata['type_selected'] = array(
                'system_name'     => $this->_params['system_name'],
                'shop_iid'     => $this->_params['shop_iid'],
                'shop_product_bn' => $this->_params['shop_product_bn'],
            );
        }
    }
    
    public function finder()
    {
        $_GET['filter']['from'] = array(
            'system_name'     => $_POST['system_name'],
            'shop_iid'     => $_POST['shop_iid'],
            'shop_product_bn' => $_POST['shop_product_bn'],
        );
        $_extra_view            = array(
            'inventorydepth' => 'admin/extra_view.html',
        );
        
        $this->set_extra_view($_extra_view);
        
        $actions[] = array(
            'label'  => '下载平台商品',
            'href'   => 'index.php?app=inventorydepth&ctl=shop_mapping&act=downloadAllGoods',
            'target' => 'dialog::{width:550,height:450,title:\'下载平台商品\'}"'
        );
        
        $params = array(
            'title'               => '平台商品列表',
            'actions'             => $actions,
            'use_buildin_recycle' => false,
            'use_buildin_filter'  => false,
            'use_buildin_export'  => true,
        );
        
        return array(
            'model'  => 'inventorydepth_mdl_shop_mapping',
            'params' => $params,
        );
    }
    
    public function set_params($params)
    {
        $this->_params = $params;
        
        return $this;
    }//End Function
    
    
}