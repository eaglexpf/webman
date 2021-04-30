<?php
namespace app\model;

use support\Model;

class BaseModel extends Model
{
    protected $cols = [];
    protected $deleted_at;

    protected function getQuery()
    {
        return self::query();
    }

    public function findOne($filter, $column = '*', $orderBy = [])
    {
        $query = $this->getQuery();
        $query = $this->_filter($query, $filter);
        $column = $this->_columns($column);
        $query = $query->select($column);
        foreach ($orderBy as $key => $value) {
            $query = $query->orderBy($key, $value);
        }
        $result = $query->first();
        return $result ? $result->toArray() : [];
    }

    public function count($filter)
    {
        $query = $this->getQuery();
        $query = $this->_filter($query, $filter);
        return $query->count();
    }

    public function getList($filter = [], $column = '*', $page = 0, $page_size = 0, $orderBy = [])
    {
        $query = $this->getQuery();
        $query = $this->_filter($query, $filter);
        $column = $this->_columns($column);
        $query = $query->select($column);
        if ($page > 0 && $page_size > 0) {
            $query = $query->offset(($page - 1) * $page_size)
                ->limit($page_size);
        }
        foreach ($orderBy as $key => $value) {
            $query = $query->orderBy($key, $value);
        }
        return $query->get()->toArray();
    }

    public function pageList($filter = [], $column = '*', $page = 0, $page_size = 0, $orderBy = [])
    {
        $count = $this->count($filter);
        $allPage = $page_size > 0 ? ceil($count/$page_size) : 1;

        $result['_page'] = [
            'allPage' => $allPage,
            'page' => $page,
            'pageSize' => $page_size,
        ];
        $result['total'] = $count;
        $result['list'] = $this->getList($filter, $column, $page, $page_size, $orderBy);
        return $result;
    }

    public function create($data)
    {
        $data = $this->_setData($data);
        if ($this->timestamps) {
            $timestamp = date('Y-m-d H:i:s', time());
            $data[self::CREATED_AT] = $timestamp;
            $data[self::UPDATED_AT] = $timestamp;
        }
        return $this->getQuery()->insertGetId($data);
    }

    public function batchInsert($data)
    {
        if ($this->timestamps) {
            $timestamp = date('Y-m-d H:i:s', time());
            foreach ($data as &$value) {
                $value = $this->_setData($value);
                $value[self::CREATED_AT] = $timestamp;
                $value[self::UPDATED_AT] = $timestamp;
            }
        }
        return $this->getQuery()->insert($data);
    }

    public function updateBy($filter, $data)
    {
        $data = $this->_setData($data);
        $query = $this->getQuery();
        $query = $this->_filter($query,$filter);
        if ($this->timestamps) {
            $timestamp = date('Y-m-d H:i:s', time());
            $data[self::UPDATED_AT] = $timestamp;
        }
        return $query->update($data);
    }

    public function deleteBy($filter)
    {
        $query = $this->getQuery();
        $query = $this->_filter($query, $filter);
        if ($this->deleted_at) {
            return $query->update([$this->deleted_at => date('Y-m-d H:i:s', time())]);
        }
        return $query->delete();
    }

    protected function _filter($query, $filters)
    {
        foreach ($filters as $field => $filter) {
            switch (true) {
                case is_int($field) && is_array($filter):
                    // 如果field是int，则filter则必须是数组
                    $query = $this->fieldIntFilter($query, $filter);
                    break;
                case is_string($field):
                    $query = $this->fieldStringFilter($query, $field, $filter);
                    break;
            }
        }
        return $query;
    }

    protected function fieldIntFilter($query, array $filter)
    {
        $count = count($filter);
        switch (true) {
            case $count === 2 && is_string($filter[0]):
                $query = $this->fieldStringFilter($query, $filter[0], $filter[1]);
                break;
            case $count === 3 && is_string($filter[0]) && is_string($filter[1]):
                $query = $this->fieldType($query, $filter[0], $filter[1], $filter[2]);
                break;
            case $count === 3 && is_array($filter[0]) && is_string($filter[1]) && is_array($filter[2]):
                // 分组查询
                $query = $this->fieldGroup($query, $filter[0], $filter[1], $filter[2]);
                break;
        }
        return $query;
    }

    protected function fieldStringFilter($query, string $field, $filter)
    {
        // ['name' => 'roc']
        // ['name' => ['a','b']]
        // ['name|in' => ['a','b']]
        // ['age|gte' => 5]
        $fields = explode('|', $field);
        $count  = count($fields);
        switch (true) {
            case $count === 1 && is_string($field):
                // 检测filter是否是数组
                if (is_array($filter)) {
                    $query = $query->whereIn($field, $filter);
                } else {
                    $query = $query->where($field, $filter);
                }
                break;
            case $count === 2 && is_string($fields[0]) && is_string($fields[1]):
                $query = $this->fieldType($query, $fields[0], $fields[1], $filter);
                break;
        }
        return $query;
    }

    protected function fieldGroup($query, array $field1, string $field2, array $field3)
    {
        // 分组查询
        $field2 = strtoupper($field2);
        $query  = $query->where(function ($query) use ($field1, $field2, $field3) {
            // 分组查询暂定为`and`和`or`两种情况
            switch ($field2) {
                case 'AND':
                    // $query
                    $query->where(function ($query) use ($field1) {
                        $query = $this->_filter($query, [$field1]);
                    })->where(function ($query) use ($field3) {
                        $query = $this->_filter($query, [$field3]);
                    });
                    break;
                case 'OR':
                    // $query
                    $query->where(function ($query) use ($field1) {
                        $query = $this->_filter($query, [$field1]);
                    })->orWhere(function ($query) use ($field3) {
                        $query = $this->_filter($query, [$field3]);
                    });
                    break;
            }
        });
        return $query;
    }

    protected function fieldType($query, string $field, string $type, $filter)
    {
        switch ($type) {
            case 'eq':
                $type = '=';
                break;
            case 'neq':
                $type = '!=';
                break;
            case 'gt':
                $type = '>';
                break;
            case 'gte':
                $type = '>=';
                break;
            case 'lt':
                $type = '<';
                break;
            case 'lte':
                $type = '<=';
                break;
        }
        switch ($type) {
            case 'in':
                $query = $query->whereIn($field, $filter);
                break;
            case 'notIn':
                $query = $query->whereNotIn($field, $filter);
                break;
            case 'between':
                $query = $query->whereBetween($field, $filter);
                break;
            case 'notBetween':
                $query = $query->whereNotBetween($field, $filter);
                break;
            case 'isNull':
                $query = $query->whereNull($field);
                break;
            case 'notNull':
                $query = $query->whereNotNull($field);
                break;
            default:
                $query = $query->where($field, $type, $filter);
        }
        return $query;
    }

    /**
     * 格式化columns
     * @param $column
     * @return array|false|string[]
     */
    protected function _columns($column)
    {
        if (is_array($column)) {
            return $column;
        }
        $column = explode(',', $column);
        if (is_array($column)) {
            return $column;
        }
        return ['*'];
    }

    protected function _setData($data)
    {
        if (empty($this->cols)) {
            return $data;
        }
        $result = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $this->cols)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}