<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class monitor_finder_extend_filter_receiver{
    function get_extend_colums(){
        $eventTemplateLib = kernel::single('monitor_event_template');
        $eventType        = $eventTemplateLib->getEventType();
        $db['event_receiver']=array (
            'columns' => array (
                'event_type' => array(
                    'type'          => $eventType,
                    'label'         => '触发事件',
                    'width'         => 100,
                    'editable'      => false,
                    'in_list'       => true,
                    'filtertype'    => 'fuzzy_search_multiple',
                    'filterdefault' => true,
                ),
            )
        );
        return $db;
    }
}

