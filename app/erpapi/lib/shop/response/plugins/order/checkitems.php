<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 *
 * @author chenping<chenping@shopex.cn>
 * @version $Id: 2013-3-12 17:23Z
 * vop重点检查
 */
class erpapi_shop_response_plugins_order_checkitems extends erpapi_shop_response_plugins_order_abstract
{

    /**
     * convert
     * @param erpapi_shop_response_abstract $platform platform
     * @return mixed 返回值
     */

    public function convert(erpapi_shop_response_abstract $platform)
    {
        $check_items = [];

        // 重点检查
        foreach ($platform->_ordersdf['order_objects'] as $k => $order_objects) {
            $objkey = $this->_get_obj_key($order_objects, $platform->object_comp_key);

            if ($order_objects['extend_item_list']['check_items']) {
                foreach ($order_objects['extend_item_list']['check_items'] as $c_k => $check_item) {

                    // if ($check_item['image_list']) {
                    //     foreach ($check_item['image_list'] as $k => $v) {
                    //         $rsp_data = kernel::single('erpapi_router_request')->set('shop', $shop_id)->purchase_getDownload(['file_id'=>$v]);
                    //     }
                    // }
                    // if ($check_item['video_list']) {
                    //     foreach ($check_item['video_list'] as $k => $v) {
                    //         $rsp_data = kernel::single('erpapi_router_request')->set('shop', $shop_id)->purchase_getDownload(['file_id'=>$v]);
                    //     }
                    // }

                    $check_items[$objkey][] = [
                        'obj_type'               => $order_objects['obj_type'],
                        'shop_goods_id'          => $order_objects['shop_goods_id'],
                        'bn'                     => $order_objects['bn'],
                        'object_comp_key'        => $objkey,
                        'channel'                => $check_item['channel'],
                        'problem_desc'           => $check_item['problem_desc'],
                        'order_label'            => $check_item['order_label'],
                        'image_fileid_list'      => $check_item['image_list'] ? json_encode($check_item['image_list']) : '',    
                        'video_fileid_list'      => $check_item['video_list'] ? json_encode($check_item['video_list']) : '',      
                        'delivery_warehouse'     => $check_item['delivery_warehouse'],
                        'order_sn'               => $check_item['order_sn'],
                        'first_classification'   => $check_item['first_classification'],
                        'second_classification'  => $check_item['second_classification'],
                        'third_classification'   => $check_item['third_classification'],
                    ];
                }
            }
        }

        return $check_items;
    }

    /**
     *
     * @return void
     * @author
     **/
    public function postCreate($order_id, $check_items)
    {
        $mdl = app::get('ome')->model('order_objects_check_items');

        foreach ($check_items as $k => $check_item) {
            foreach ($check_item as $key => $value) {
                $value['order_id'] = $order_id;
                $mdl->save($value);
            }
        }
    }

    /**
     *
     * @param Array
     * @return void
     * @author
     **/
    public function postUpdate($order_id, $check_items)
    {
        $mdl = app::get('ome')->model('order_objects_check_items');
        $mdl->delete(['order_id'=>$order_id]);

        foreach ($check_items as $k => $check_item) {
            foreach ($check_item as $key => $value) {
                $value['order_id'] = $order_id;
                $mdl->save($value);
            }
        }
    }

    public function _get_obj_key($object, $object_comp_key = 'bn-shop_goods_id-obj_type')
    {
        $objkey = '';
        foreach (explode('-', $object_comp_key) as $field) {
            $objkey .= ($object[$field] ? trim($object[$field]) : '') . '-';
        }

        return sprintf('%u', crc32(ltrim($objkey, '-')));
    }
}
