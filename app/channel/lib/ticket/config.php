<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * CONFIG
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class channel_ticket_config 
{
    /**
     * formatConfig
     * @param mixed $config 配置
     * @param mixed $channel channel
     * @return mixed 返回值
     */

    public function formatConfig($config, $channel)
    {

        // 如果有channel_id 则补充channel_adapter
        if(isset($channel['channel_id']) && $channel['channel_id']){
            $channelMdl = app::get('channel')->model('channel');
            $oldChannel = $channelMdl->db_dump($channel['channel_id'], '*');
            $channel['channel_adapter'] = $oldChannel['channel_adapter'];
        }
        
        if(!isset($channel['channel_adapter']) || !$channel['channel_adapter']){
            return $channel;
        }
        
        // 如果没有节点id,则自动补充
        if ( !$channel['node_id'] ){
            switch ($channel['channel_adapter']) {
                case 'openapi':
                    $channel['node_id'] = sprintf('o%u', crc32(utils::array_md5($config) . kernel::base_url()));
                    break;
                default:
                    break;
            }
        }
        
        $channel['config'] = serialize($config);
        
        return $channel;
   }
}
