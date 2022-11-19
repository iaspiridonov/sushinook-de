<?php namespace Src\Controller;


use Core\Facade\Template;
use Core\Gateway\Subjects;
use Core\Service\Registry;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Core\Facade\App;
use Core\Service\Locale;
class Catalog extends AbstractController
{
    public function bootRouting()
    {
        $this->GET('/', 'index');
        $this->GET('/{id:\d+}[-{name:[a-z\d\-]+}]', 'catalogList');
        $this->GET('/{parentID:\d+}-{parentName:[a-z\d\-]+}/{id:\d+}[-{name:[a-z\d\-]+}]', 'productsList');
        $this->GET('/product/{id:\d+}[-{name:[a-z\d\-]+}]', 'productPage');
    }

    public function productPage($id) {
        $product = Subjects::of('Product')->with(['shop', 'images', 'parent'])->find($id);
        $breadcrumbs[$product->url()] = $product->name;
        $item = $product;
        while(($item = $item->parent) && !empty($item->id)) {
            $breadcrumbs[$item->url()] = $item->name;
        }
        $breadcrumbs['/'.Locale::getLocale().'/catalog/'] = 'Каталог товаров';
        $breadcrumbs = array_reverse($breadcrumbs);

        $other_products = $product->shop->products()->select(function($select) {
            $select->order([new Expression("RAND() ASC")]);
            $select->limit(10);
        })->get();
        $category_products = $product->parent->products()->select(function($select) {
            $select->order([new Expression("RAND() ASC")]);
            $select->limit(10);
        })->get();
        return $this->html(Template::render('src/catalog/product',[
                                                                    'product'=>$product, 
                                                                    'other_products'=>$other_products, 
                                                                    'category_products'=>$category_products,
                                                                    'breadcrumbs'=>$breadcrumbs
                                                                ]));
    }

    public function productsList($parentID, $parentName, $id, $name) {
        $cat = Subjects::of('Category')->find($id);
        $breadcrumbs[$cat->url()] = $cat->name;
        $item = $cat;
        while(($item = $item->parent) && !empty($item->id)) {
            $breadcrumbs[$item->url()] = $item->name;
        }
        $breadcrumbs['/'.Locale::getLocale().'/catalog/'] = 'Каталог товаров';
        $breadcrumbs = array_reverse($breadcrumbs);
        $products = $cat->products()->paginate(16);
        return $this->html(Template::render('src/catalog/productList',['products'=>$products,'breadcrumbs'=>$breadcrumbs]));
    }

    public function catalogList($id, $name) {
        $cat = Subjects::of('Category')->find($id);
        $breadcrumbs[$cat->url()] = $cat->name;
        $item = $cat;
        while(($item = $item->parent) && !empty($item->id)) {
            $breadcrumbs[$item->url()] = $item->name;
        }
        $breadcrumbs['/'.Locale::getLocale().'/catalog/'] = 'Каталог товаров';
        $breadcrumbs = array_reverse($breadcrumbs);
        
        $categories = $cat->categories;
        return $this->html(Template::render('src/catalog/catalogList',['categories'=>$categories,'breadcrumbs'=>$breadcrumbs]));
    }

    public function index() {
        $breadcrumbs['/'.Locale::getLocale().'/catalog/'] = 'Каталог товаров';
        $categories = Subjects::of('Category')->select(['is_related'=>0])->get();
        return $this->html(Template::render('src/catalog/catalogList',['categories'=>$categories,'breadcrumbs'=>$breadcrumbs]));
    }
}