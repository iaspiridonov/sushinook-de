<?php namespace Core\Collection;

use Core\Service\Pagination;

class BaseCollection extends \Illuminate\Support\Collection
{
    /**
     * @var Pagination
     */
    protected $pagination;

    public function populate($items)
    {
        $this->items = $items;
    }

    public function setPagination(Pagination $paginator)
    {
        $this->pagination = $paginator;
        return $this;
    }

    public function pagination($attributeName = null)
    {
        return $attributeName ? $this->pagination->get($attributeName) : $this->pagination;
    }
}