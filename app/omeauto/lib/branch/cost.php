<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * ============================
 * @Author:   yaokangming
 * @Version:  1.0
 * @DateTime: 2020/9/8 17:20:23
 * @describe: 成本优先获取仓库
 * ============================
 */
class omeauto_branch_cost extends omeauto_branch_abstract {
    private $costResult = '';
    private $orderBn = array();
    private $areaId;
    private $branchIdName;
    private $isBranchGroup;

    /**
     * 处理
     * @param mixed $branchIds ID
     * @param mixed $group group
     * @param mixed $branchInfo branchInfo
     * @return mixed 返回值
     */

    public function process($branchIds, &$group, $branchInfo) {
        list($usec, $sec) = explode(" ", microtime());
        $call_start_time = $usec + $sec;
        $this->isBranchGroup = false;
        $bgData = $group->getBranchGroup();
        if($bgData) {
            $this->isBranchGroup = true;
            $branchIds = array();
            foreach ($bgData as $k => $v) {
                $branchIds = array_merge($branchIds, $v['branch_id']);
            }
        }
        $branchCode = app::get('ome')->model('branch')->getList('branch_id, branch_bn, b_type', array('branch_id'=>$branchIds,'check_permission' => 'false'));
        $this->branchIdName = array();
        foreach ($branchCode as $k => $v) {
            $this->branchIdName[$v['branch_id']] = array(
                'bg_id' => $v['branch_id'],
                'branch_id'=>$v['branch_id'], 
                'b_type'=>$v['b_type'], 
                'name'=>$v['branch_bn'].'('.$v['branch_id'].')'
            );
        }
        $orders = $group->getOrders();
        if($this->isBranchGroup) {
            $this->setCostResultTr('仓库分组', $bgData, 'name');
            $arrBranchCost = $this->getBranchGroupProduct($orders, $bgData);
        } else {
            $this->setCostResultTr('仓库', $this->branchIdName, 'name');
            $arrBranchCost = $this->getBranchProduct($orders, $branchIds, $branchInfo);
        }
        $this->setCostResultTr('商品数', $arrBranchCost, 'nums');
        $arrBranchCost = $this->getProductWeight($arrBranchCost);
        $this->setCostResultTr('商品重量', $arrBranchCost, 'weight');
        $arrBranchCost = $this->getBsCost($arrBranchCost);
        $this->setCostResultTr('商品总重量', $arrBranchCost, 'weight');
        $this->setCostResultTr('商品成本', $arrBranchCost, 'goods_cost');
        $this->setCostResultTr('订单处理费', $arrBranchCost, 'bs_cost');
        $arrBranchCost = $this->getLogisticsCost($arrBranchCost);
        $this->setCostResultTr('物流成本', $arrBranchCost, 'corp_cost');
        if($this->isBranchGroup) {
            $result = $this->dealBranchGroupCostResult($arrBranchCost, $group);
        } else {
            $result = $this->dealBranchCostResult($arrBranchCost, $group, $branchIds);
        }
        list($usec, $sec) = explode(" ", microtime());
        $call_end_time = $usec + $sec;
        $result['spendtime'] = $call_end_time - $call_start_time;
        $this->writeSuccessLog($result);
        return json_decode($result['select_branch_id'], 1);
    }

    /**
     * cmp_cost
     * @param mixed $a a
     * @param mixed $b b
     * @return mixed 返回值
     */
    public function cmp_cost($a, $b) {
        if($a['cost'] === $b['cost']) {
            return 0;
        }
        return $a['cost'] < $b['cost'] ? -1 : 1;
    }

    /**
     * cmp_bg_id
     * @param mixed $a a
     * @param mixed $b b
     * @return mixed 返回值
     */
    public function cmp_bg_id($a, $b) {
        if($a['bg_id'] === $b['bg_id']) {
            return 0;
        }
        return $a['bg_id'] < $b['bg_id'] ? -1 : 1;
    }

    private function setCostResultTr($ftd, $arr, $field) {
        uasort($arr, array($this, 'cmp_bg_id'));
        $this->costResult .= '<tr><td>' . $ftd;
        $currentId = 0;
        foreach ($arr as $k => $v) {
            $td = ($v['td_code'] ? $v['td_code'] . ':' : '').$v[$field];
            if($v['bg_id'] != $currentId) {
                $this->costResult .= '</td><td>'.$td;
                $currentId = $v['bg_id'];
                continue;
            }
            $this->costResult .= '<br/>'.$td;
        }
        $this->costResult .= '</td></tr>';
    }

    private function getBranchGroupProduct($orders, $bgData) {
        $bmBn = array();
        foreach ($orders as $k => $order) {
            !in_array($order['order_bn'], $this->orderBn) && $this->orderBn[] = $order['order_bn'];
            foreach ($order['objects'] as $ok => $object) {
                foreach ($object['items'] as $ik => $item) {
                    $bmId = $item['product_id'];
                    $bmBn[$bmId] = $item['bn'];
                }
            }
        }
        list($area_prefix,$area_chs,$area_id) = explode(':',$order['ship_area']);
        $this->areaId = $area_id;
        $tmpBranchCost = array();
        foreach ($bgData as $v) {
            foreach ($v['branch_product'] as $bId => $arrp) {
                foreach ($arrp as $bmId => $nums) {
                    $index = $v['bg_id'] .'-' . $bId . '-' . $bmId;
                    $tmpBranchCost[$index] = array(
                        'bg_id' => $v['bg_id'],
                        'branch_id' => $bId,
                        'bm_id' => $bmId,
                        'td_code' => $this->branchIdName[$bId]['name'] . '-' . $bmBn[$bmId],
                        'nums' => $nums
                    );
                }
            }
        }
        return $tmpBranchCost;
    }

    private function getBranchProduct($orders, $branchIds, $branchInfo) {
        $tmpBranchCost = array();//key = branch_id - product_id
        $this->orderBn = array();
        $bmBn = array();
        foreach ($branchIds as $branchId) {
            foreach ($orders as $k => $order) {
                !in_array($order['order_bn'], $this->orderBn) && $this->orderBn[] = $order['order_bn'];
                foreach ($order['objects'] as $ok => $object) {
                    foreach ($object['items'] as $ik => $item) {
                        $bmId = $item['product_id'];
                        $index = $branchId . '-' . $bmId;
                        $nums = $item['nums'];
                        $bmBn[$bmId] = $item['bn'];
                        if($tmpBranchCost[$index]) {
                            $tmpBranchCost[$index]['nums'] += $nums;
                        } else {
                            $tmpBranchCost[$index] = array(
                                'bg_id' => $branchId,
                                'branch_id' => $branchId,
                                'bm_id' => $bmId,
                                'td_code' => $item['bn'],
                                'nums' => $nums
                            );
                        }
                    }
                }
            }
        }

        if($branchInfo) {
            $tmpBranchCost =[];
            foreach ($branchInfo as $branchId => $value) {
                if(!in_array($branchId, $branchIds)) {
                    continue;
                }
                foreach ($value['store'] as $bmId => $nums) {
                    $index = $branchId . '-' . $bmId;
                    $tmpBranchCost[$index] = array(
                        'bg_id' => $branchId,
                        'branch_id' => $branchId,
                        'bm_id' => $bmId,
                        'td_code' => $bmBn[$bmId],
                        'nums' => $nums
                    );
                }
            }
        }
        list($area_prefix,$area_chs,$area_id) = explode(':',$order['ship_area']);
        $this->areaId = $area_id;
        return $tmpBranchCost;
    }

    private function getProductWeight($arrBranchCost) {
        $bmIds = array();
        foreach ($arrBranchCost as $v) {
            $bmIds[$v['bm_id']] = $v['bm_id'];
        }
        $basicMaterialExtObj = app::get('material')->model('basic_material_ext');
        $products = $basicMaterialExtObj->getList('bm_id,weight', array('bm_id'=>$bmIds));
        $arrBmWeight = array();
        foreach ($products as $v) {
            $arrBmWeight[$v['bm_id']] = $v['weight'];
        }
        foreach ($arrBranchCost as $k => $v) {
            $arrBranchCost[$k]['weight'] = bcmul($v['nums'], $arrBmWeight[$v['bm_id']], 3);
        }
        return $arrBranchCost;
    }

    private function getBsCost($arrBranchCost) {
        if(!app::get('dealer')->is_installed()) {
            return [];
        }
        $branchIds = array();
        $bmIds = array();
        foreach ($arrBranchCost as $v) {
            $branchIds[$v['branch_id']] = $v['branch_id'];
            $bmIds[$v['bm_id']] = $v['bm_id'];
        }
        $dealerBusinessBranch = app::get('dealer')->model('business_branch')->getList('*', array('branch_id'=>$branchIds));
        $bsIds = array();
        $branchIdBsId = array();
        foreach ($dealerBusinessBranch as $v) {
            $bsIds[$v['bs_id']] = $v['bs_id'];
            $branchIdBsId[$v['branch_id']] = $v['bs_id'];
        }
        $dealerGoods = app::get('dealer')->model('goods')->getList('bs_id,bm_id,cost', array('bs_id'=>$bsIds, 'bm_id'=>$bmIds));
        $dealerGoodsCost = array();
        foreach ($dealerGoods as $v) {
            $dealerGoodsCost[$v['bs_id']][$v['bm_id']] = $v['cost'];
        }
        $tmpBranchCost = array();
        foreach ($arrBranchCost as $k => $v) {
            $index = $v['bg_id'] . '-' . $v['branch_id'];
            $bsId = $branchIdBsId[$v['branch_id']];
            $goodsCost = bcmul($v['nums'], $dealerGoodsCost[$bsId][$v['bm_id']], 3);
            if($tmpBranchCost[$index]) {
                $tmpBranchCost[$index]['weight'] += $v['weight'];
                $tmpBranchCost[$index]['nums'] += $v['nums'];
                $tmpBranchCost[$index]['hang'] += 1;
                $tmpBranchCost[$index]['goods_cost'] += $goodsCost;
            } else {
                $tmpBranchCost[$index] = array(
                    'bg_id' => $v['bg_id'],
                    'branch_id' => $v['branch_id'],
                    'td_code' => ($this->isBranchGroup ? $this->branchIdName[$v['branch_id']]['name'] : ''),
                    'weight' => $v['weight'],
                    'nums' => $v['nums'],
                    'hang' => 1,
                    'goods_cost' => $goodsCost
                );
            }
        }
        $dealerBusiness = app::get('dealer')->model('business')->getList('bs_id, deal_cost', array('bs_id'=>$bsIds));
        $dbCostExp = array();
        foreach ($dealerBusiness as $k => $v) {
            $dbCostExp[$v['bs_id']] = $v['deal_cost'];
        }
        foreach ($tmpBranchCost as $k => $v) {
            $exp = $dbCostExp[$branchIdBsId[$v['branch_id']]];
            $bsCost = utils::cal_bs_fee($exp,$v['weight'],$v['nums'],$v['hang']);
            $tmpBranchCost[$k]['bs_cost'] = $bsCost;
            $tmpBranchCost[$k]['cost'] = bcadd($bsCost, $tmpBranchCost[$k]['goods_cost'], 3);
        }
        return $tmpBranchCost;
    }

    private function getLogisticsCost($arrBranchCost) {
        $branchIds = array();
        foreach ($arrBranchCost as $v) {
            $branchIds[$v['branch_id']] = $v['branch_id'];
        }
        //处理指定物流公司 corp_config
        $mdl_ome_branch_corp = app::get('ome')->model("branch_corp");
        //获取原有的关系数据
        $rs_branch_corp = $mdl_ome_branch_corp->getList("*",array("branch_id"=>$branchIds));
        $branchCorp = array();
        $corpIds = array();
        foreach ($rs_branch_corp as $k => $v) {
            $branchCorp[$v['branch_id']][] = $v['corp_id'];
            $corpIds[$v['corp_id']] = $v['corp_id'];
        }
        $corpRows = app::get('ome')->model('dly_corp')->getList('corp_id,name', array('corp_id'=>$corpIds));
        $corpName = array();
        foreach ($corpRows as $v) {
            $corpName[$v['corp_id']] = $v['name'];
        }
        $dlyModel = app::get('ome')->model('delivery');
        $tmpBranchCost = array();
        foreach ($arrBranchCost as $k => $v) {
            if(empty($branchCorp[$v['branch_id']])) {
                #门店仓取门店配送
                $corpId = $this->branchIdName[$v['branch_id']]['b_type'] == 2 ? 2 : 0;
                $index = $v['bg_id'] . '-' . $v['branch_id'] . '-' . $corpId;
                $tmpBranchCost[$index] = $v;
                $tmpBranchCost[$index]['corp_id'] = $corpId;
                $tmpBranchCost[$index]['td_code'] = $this->branchIdName[$v['branch_id']]['b_type'] == 2 ? '门店配送' : '无物流';
                $tmpBranchCost[$index]['corp_cost'] = 0;
                continue;
            }
            foreach ($branchCorp[$v['branch_id']] as $corpId) {
                $index = $v['bg_id'] . '-' . $v['branch_id'] . '-' . $corpId;
                $tmpBranchCost[$index] = $v;
                $tmpBranchCost[$index]['corp_id'] = $corpId;
                $tmpBranchCost[$index]['td_code'] = ($this->isBranchGroup ? $this->branchIdName[$v['branch_id']]['name'] . '-' : '') . $corpName[$corpId].'('.$corpId.')';
                $corpCost = $dlyModel->getDeliveryFreight($this->areaId,$corpId,$v['weight']);
                $tmpBranchCost[$index]['corp_cost'] = $corpCost;
                $tmpBranchCost[$index]['cost'] += $corpCost;
            }
        }
        return $tmpBranchCost;
    }

    private function dealBranchCostResult($arrBranchCost, &$group, $branchIds) {
        $this->setCostResultTr('总成本', $arrBranchCost, 'cost');
        uasort($arrBranchCost, array($this, 'cmp_cost'));
        $minCost = current($arrBranchCost);
        $selectBranchId = array();
        $branchIdCorpId = array();
        foreach ($arrBranchCost as $k => $v) {
            if($v['cost'] == $minCost['cost']) {
                $branchIdCorpId[$v['branch_id']] = $v['corp_id'];
                $index = array_search($v['branch_id'], $branchIds);
                $selectBranchId[$index] = $v['branch_id'];
            } else {
                break;
            }
        }
        $group->setBranchIdCorpId($branchIdCorpId);
        ksort($selectBranchId);
        $result = array(
            'select_branch_id' => json_encode($selectBranchId),
            'branch_id_corp_id' => json_encode($branchIdCorpId),
        );
        return $result;
    }

    private function dealBranchGroupCostResult($arrBranchCost, &$group) {
        $bgData = $group->getBranchGroup();
        $corpCost = array();
        $goodsCost = array();
        $bsCost = array();
        foreach ($arrBranchCost as $k => $v) {
            $corpCost[$v['bg_id']][$v['branch_id']][$v['corp_id']] = $v['corp_cost'];
            $goodsCost[$v['bg_id']][$v['branch_id']] = $v['goods_cost'];
            $bsCost[$v['bg_id']][$v['branch_id']] = $v['bs_cost'];
        }
        $bgCost = array();
        foreach ($bgData as $k => $v) {
            $bgId = $v['bg_id'];
            $tmpBranchCorp = array();
            foreach ($goodsCost[$bgId] as $bId => $gCost) {
                $bCost = $bsCost[$bgId][$bId];
                $cCost = min($corpCost[$bgId][$bId]);
                $tmpBranchCorp[$bId] = array_search($cCost, $corpCost[$bgId][$bId]);
                $bgCost[$bgId] += $gCost + $bCost + $cCost;
            }
            $bgData[$k]['branch_corp'] = $tmpBranchCorp;
        }
        $bgCostTr = array();
        foreach ($bgCost as $k => $v) {
            $bgCostTr[] = array('bg_id'=>$k, 'cost'=>$v);
        }
        $this->setCostResultTr('总成本', $bgCostTr, 'cost');
        $minCost = min($bgCost);
        $selectBranchId = array();
        foreach ($bgData as $k => $v) {
            if($bgCost[$v['bg_id']] > $minCost) {
                unset($bgData[$k]);
                continue;
            }
            $selectBranchId = array_merge($selectBranchId, $v['branch_id']);
        }
        $selectBranchId = array_unique($selectBranchId);
        $group->setBranchGroup($bgData);
        $result = array(
            'select_branch_id' => json_encode($selectBranchId),
            'branch_group' => var_export($bgData, 1),
        );
        return $result;
    }

    protected function writeSuccessLog($result) {
        $apilogModel = app::get('ome')->model('api_log');
        $log_id = $apilogModel->gen_id();
        $result['order_bn'] = json_encode($this->orderBn);
        $logsdf = array(
            'log_id'        => $log_id,
            'task_name'     => '成本计算结果',
            'status'        => 'success',
            'worker'        => '',
            'params'        => json_encode(array('branch.cost', array('cost_result'=>'<table style="table-layout: fixed;">'.$this->costResult.'</table>')), JSON_UNESCAPED_UNICODE), #longtext
            'msg'           => json_encode($result), #text json字符串
            'log_type'      => '',
            'api_type'      => 'response',
            'memo'          => '',
            'original_bn'   => $this->orderBn[0],
            'createtime'    => time(),
            'last_modified' => time(),
            'msg_id'        => '',
        );
        $apilogModel->insert($logsdf);
    }
}