<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class console_event_trigger_iso
{
    /**
     * 查询出入单结果
     *
     * @return void
     * @author
     **/
    public function search($iso_id)
    {
        $iso = app::get('taoguaniostockorder')->model("iso")->db_dump($iso_id);

        $wms_id = kernel::single('ome_branch')->getWmsIdById($iso['branch_id']);

        $data = array(
            'out_order_code' => $iso['out_iso_bn'],
            'stockin_bn'     => $iso['iso_bn'],
        );

        $io = kernel::single('siso_receipt_iostock')->getIoByType($iso['type_id']);

        if ($io){
            return kernel::single('console_event_trigger_otherstockin')->search($wms_id, $data);
        } else {
            return kernel::single('console_event_trigger_otherstockout')->search($wms_id, $data);
        }
    }
}
