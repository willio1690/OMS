--[[
SKU仓库存缓存在REDIS，通过LUA脚本重置库存和冻结实现原子性

@author: chenping@shopex.cn
@time: 2024-07-10
--]]

-- 更新库存函数
local function update(sku_id, fields)
    -- 增加库存
     return redis.call('HMSET', sku_id, unpack(fields))
end



-- 更新冻结
for i, v in ipairs(KEYS) do
    local s = {}

    -- 使用 gmatch 和正则表达式来拆分字符串
    for piece in string.gmatch(ARGV[i], "[^,]+") do
        table.insert(s, piece)
    end

    local fields = {}
    table.insert(fields, 'store')
    table.insert(fields, tonumber(s[1]))
    table.insert(fields, 'store_freeze')
    table.insert(fields, tonumber(s[2]))

    local result = update(v, fields)

    if not result then
        return {300, 'FAIL_STOCK_IN_REDIS', v}
    end
end

return {0, 'success'}