<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class invoice_mdl_channel extends dbeav_model
{
    /**
     * 获取店铺所属的电子发票渠道
     * @todo：一个店铺只能在一个渠道
     * 
     * @param string $shop_id
     * @param string $mode
     * @return array
     */
    public function get_channel_info($shop_id, $mode=1)
    {
        if($mode ==1){
            $field = 's.einvoice_operating_conditions,s.channel_id,c.channel_type,c.extend_data channel_extend_data,s.skpdata,s.kpddm,eqpttype,s.billing_shop_node_id,s.tax_rate,c.node_id,c.node_type,c.golden_tax_version';
            $shop_id = '%'. $shop_id .'%';
            
            $sql = "SELECT %s FROM sdb_invoice_order_setting AS s LEFT JOIN sdb_invoice_channel AS c ON s.channel_id=c.channel_id 
                    WHERE s.shopids LIKE '%s' AND s.mode='%s'";
            
            $sql = sprintf($sql, $field, $shop_id, $mode);
        }else{
            $sql = "select * from sdb_invoice_order_setting  where mode='0'";
        }
        
        $_row = $this->db->selectRow($sql);
        
        return $_row;
    }

    public function getChannelByType($channelType)
    {
        $filter = ['channel_type' => $channelType];
        $channel = $this->dump($filter);

        if(!$channel){
            return false;
        }

        return $channel;
    }

    /**
     * 发票接口绑定调度
     * @param $data
     * @param $response
     * @return array|bool|int|void
     */
    public function bindChannelCallback($channel,$result)
    {
        if(!isset($channel['channel_id']) || !$channel['channel_id']){
            return [false,"渠道主键不存在"];
        }

        $response = $result['data'];

        if(!isset($response['info'], $response['info']['node_id']) || !$response['info']['node_id']){
            return [false, "绑定节点号不存在"];

        }

        $updateData = [
            'node_id' => $response['info']['node_id'],
            'node_type' => $response['info']['node_type'],
        ];

        $filter = [
            'channel_id' => $channel['channel_id']
        ];

        $updateRs = $this->update($updateData, $filter);

        if($updateRs === false){
            return [false, "更新节点号失败"];
        }
        return [true, "绑定成功"];
    }
}