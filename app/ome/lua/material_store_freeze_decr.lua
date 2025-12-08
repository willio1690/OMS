--[[
SKU物料总库存缓存在REDIS，通过LUA脚本扣减冻结实现原子性

@author: chenping@shopex.cn
@time: 2024-07-07
--]]

-- 检查库存是否充足
local function check(sku_id, quantity)
    local data = redis.call('HMGET', sku_id, 'store', 'store_freeze')

    -- redis未缓存到库存, 返回错误
    if not data[1] then
        return {100, 'MISSING_SKU_STOCK', sku_id}
    end

    -- redis未缓存到冻结, 返回错误
    if not data[2] then
        return {100, 'MISSING_SKU_FREEZE', sku_id}
    end

    return {0, 'success', data}
end

-- 更新冻结函数
local function update(sku_id, quantity, max_freeze)
    local actual_quantity = tonumber(quantity)

    -- -- 如果冻结数量不足，则以当前冻结数量为准，最终冻结数不为负
    -- if max_freeze and tonumber(max_freeze) < math.abs(actual_quantity) then
    --     actual_quantity = -tonumber(max_freeze)
    -- end

    return redis.call('HINCRBY', sku_id, 'store_freeze', actual_quantity)
end

-- 存储检查结果
local check_results = {}

--[[
检查库存是否充足
KEYS格式: #节点ID##stock:material:#物料ID#, 示例: 1661661666#stock:material:1
--]]
for i, v in ipairs(KEYS) do
    local result = check(v, tonumber(ARGV[i]))

    if result[1] ~= 0 then
        return result
    end

    check_results[v] = result[3]
end

-- 扣减冻结
for i, v in ipairs(KEYS) do
    local max_freeze = check_results[v][2]

    local result = update(v, tonumber(ARGV[i]), max_freeze)

    if not result then
        return {300, 'FAIL_SKU_FREEZE', v}
    end
end

return {0, 'success'}