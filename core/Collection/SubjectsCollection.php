<?php namespace Core\Collection;

use Core\Model\Subject;
use Core\Gateway;

class SubjectsCollection extends BaseCollection
{
    public function load($relations)
    {
        $params = false;

        if(is_array($relations)) {
            $params = $relations;
        }else if(is_string($relations)) {
            $params = [$relations];
        }

        if(!$params) {
            throw new \InvalidArgumentException('Wrong $relations argument');
        }

        $grouped = $this->groupBy(function($item) {
            /** @var Subject $item */
            return $item->getType('name');
        });

        $grouped->each(function($subjectsCollection, $typeName) use ($params){
            Gateway\Relations::of($typeName)->load($subjectsCollection, $params);
        });

        return $this;
    }
}