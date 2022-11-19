<?php namespace Core\Service;

use Core\Facade\Template;

class Pagination
{
    protected $total;
    protected $maxPages;
    protected $perPage;
    protected $page;
    protected $pageName;
    protected $path;

    public function __construct($total, $perPage = 15, $pageName = 'page', $path = null)
    {
        $this->total = (int)$total;

        $this->maxPages = (int)ceil($total/$perPage);

        $this->perPage = (int)$perPage;

        $this->page = (int)(($pageFromQuery = Registry::get('http.request.query.'.$pageName, 1)) < $this->maxPages ? $pageFromQuery : $this->maxPages) ?: 1;

        $this->pageName = $pageName;

        $this->path = $path ?: $_SERVER['REQUEST_URI'];
    }

    public function next()
    {
        $i = 0;

        while($i++ < $this->maxPages) {
            yield $i => $this->pageUrl($i);
        }
    }

    public function toArray()
    {
        $rows = [];

        foreach ($this->next() as $page => $url) {
            $rows[$page] = $url;
        }

        return $rows;
    }

    protected function pageUrl($page)
    {
        if (strstr($this->path, '&'.$this->pageName)) {
            return preg_replace('/(\&'.$this->pageName.')=(\d+)/', '$1='.$page, $this->path);
        } else if (strstr($this->path, '?'.$this->pageName)) {
            return preg_replace('/(\?'.$this->pageName.')=(\d+)/', '$1='.$page, $this->path);
        }

        return ($this->path.(Registry::get('http.request.query') ? '&' : '?').$this->pageName.'='.$page);
    }

    public function previousPageUrl()
    {
        if ($this->page > 1) {
            return $this->pageUrl($this->page - 1);
        }

        return null;
    }

    public function currentPageUrl()
    {
        return $this->pageUrl($this->page);
    }

    public function nextPageUrl()
    {
        if ($this->page < $this->maxPages) {
            return $this->pageUrl($this->page + 1);
        }

        return null;
    }

    public function get($attributeName)
    {
        if (property_exists($this, $attributeName)) {
            return $this->$attributeName;
        }

        return null;
    }

    public function render($template)
    {
        return Template::render($template, ['pagination' => $this]);
    }
}