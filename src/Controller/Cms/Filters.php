<?php
namespace Src\Controller\Cms;
use Core\Middleware\Controller;
use Src\Controller\Cms;
use Core\Facade\Template;
use Core\Service\Locale;
use Core\Service\Registry;
use Core\Gateway\Subjects;
use Src\Model;
use Core\Gateway;


class Filters extends Controller {
    protected $auth;

    public $categories;

    protected function bootProcess()
    {
        $this->middleware(($this->auth = new Cms));

    }
    protected function bootDispatch()
    {
        Template::defaults([
            'Auth' => $this->auth,
            'alerts' => Registry::pull('session.alerts'),
            'path' => [
                'controller' => $this->path(),
                'uri' => $this->path(Registry::get('http.request.path'))
            ]
        ]);
    }


    protected function bootRouting()
    {
        $this->GET('/{id:\d+}', 'indexPage');
        $this->POST('/{id:\d+}', 'index');
    }

    public function index($id) {
        $filters = Registry::get('http.request.body.filters');
        $category = Subjects::of('Category')->find($id);
        $product = $category->products->first();
        $characteristics = $product->characters;

        $oldfilters = $category->filters;
        $oldfilters->each(function ($subject) {
            $subject->delete();
        });
        foreach($filters as $filter) {
            list($etim, $name) = explode("|", $filter);
            ($newFilter = new Model\Filter(['EtimClass'=>$etim, 'name'=>$name]));
            $category->filters()->save($newFilter);
        }

        return $this->redirect('/ru/cms/filters/'.$category->id);
    }

    public function indexPage($id) {
        $category = Subjects::of('Category')->find($id);
        $product = $category->products->first();
        $characteristics = $product->characters;
        $currentFilters = [];

        foreach($category->filters as $filter) {
            $currentFilters[] = $filter->EtimClass;
        }
        return $this->html(Template::render('cms/module/filters/index',['category'=>$category, 'characters'=>$characteristics, 'currentFilters'=>$currentFilters]));
    }

}

?>