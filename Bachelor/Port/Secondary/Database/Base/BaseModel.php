<?php

namespace Bachelor\Port\Secondary\Database\Base;

use Bachelor\Domain\Base\BaseDomainModel;
use Bachelor\Port\Secondary\Database\Base\Traits\FilterTrait;
use Bachelor\Port\Secondary\Database\Base\Traits\SearchTrait;
use Bachelor\Utility\Enums\Status;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as CollectionDao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseModel extends Model
{
    use SearchTrait, FilterTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /*
     * Filer that's applicable for the model
     *
     * @var array
     */
    protected array $filter = [
        'status' => [
            'Active' => Status::Active,
            'Inactive' => Status::Inactive
        ]
    ];

    /*
     * The columns that should be searched.
     *
     * @var array
     */
    public static array $search = ['id', 'name'];

    /**
     * The number of models to return for pagination.
     *
     * @var int
     */
    protected $perPage = 10;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    /**
     * Get the search params
     *
     * @param $search
     * @return array
     */
    public static function getSortedSearch(array $search): array
    {
        // Initialize the search params
        $finalSearchKeys = [
            'id' => [],
            'name' => []
        ];

        foreach ($search as $eachSearchKey)
            $finalSearchKeys = self::sortSearchKeys($finalSearchKeys, $eachSearchKey);

        return $finalSearchKeys;
    }

    /**
     * Get filter for the model
     *
     * @return array
     */
    public function getFilter(): array
    {
        return $this->filter;
    }

    /**
     * Build index query for the model
     *
     * @param array $param
     * @return Builder
     */
    public function buildIndexQuery(array $param): Builder
    {
        $query = self::newModelQuery();

        if (!empty($param['search']))
            $query = self::applySearch($query, $param['search']);

        if (!empty($param['filter']))
            $query = self::applyFilers($query, $param['filter']);

        return $query;
    }

    /**
     * Create Domain Model object from this model DAO
     */
    abstract public function toDomainEntity();

    /**
     * Pull data from Domain Model object to this model DAO for saving
     * @param $model
     */
    abstract protected function fromDomainEntity(BaseDomainModel $model);

    /**
     * Get all the data in the model
     *
     * @param array|null $columns
     * @return mixed
     */
    public function getAllData(array $columns = null)
    {
        return $this->newModelQuery()->get($columns);
    }

    /**
     * Get specific data from the model
     *
     * @param $value
     * @param string $column
     * @return mixed
     */
    public function getSpecificData($value, string $column = 'id')
    {
        return $this->newModelQuery()->where($column, $value);
    }

    /**
     * @param BaseDomainModel $model
     * @param array $options
     * @return BaseDomainModel
     */
    public function saveData($model, array $options = [])
    {
        $this->fromDomainEntity($model);

        $this->exists = (bool) $this->getKey();

        if (!parent::save($options)) {
            throw new Exception('Failed to saveData in ' . get_class($this) . 'for id' . $this->getKey());
        }

        $model->setId($this->getKey());

        return $model;
    }
}
