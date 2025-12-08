<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class erpapi_shop_matrix_shopex_ecstore_request_v3_aftersale extends erpapi_shop_matrix_shopex_request_aftersale{


    protected function __formatAfterSaleParams($returninfo,$status) {
        $params = parent::__formatAfterSaleParams($returninfo,$status);
        return $params;
    }


}