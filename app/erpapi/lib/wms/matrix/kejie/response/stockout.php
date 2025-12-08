<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 出库单
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_matrix_kejie_response_stockout extends erpapi_wms_response_stockout
{    
    /**
     * wms.stockout.status_update
     *
     **/

    public function status_update($params){
        $params = parent::status_update($params);
        if (!$params['items'] && $params['io_status'] == 'FINISH'){
            $params['items'] = $this->_format_items($params);
        }
        return $params;
    }

    
    private function _format_items($params){
        $rp_itemObj = app::get('purchase')->model('returned_purchase_items');
        if ($params['io_type'] == 'PURCHASE_RETURN'){

            
            $items = $rp_itemObj->db->select("SELECT i.bn,i.num FROM sdb_purchase_returned_purchase as p LEFT JOIN sdb_purchase_returned_purchase_items as i ON p.rp_id=i.rp_id WHERE p.rp_bn='".$params['io_bn']."'");
        }else{
            $items = $rp_itemObj->db->select("SELECT i.bn,i.nums as num FROM sdb_taoguaniostockorder_iso as iso LEFT JOIN sdb_taoguaniostockorder_iso_items as i ON iso.iso_id=i.iso_id WHERE iso.iso_bn='".$params['io_bn']."'");
        }
        if ($items){
            return $items;
        }
    }
}
