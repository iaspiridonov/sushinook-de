<?php namespace Src\Controller\Cabinet;

use Core\Service\Locale;
use Src\Controller\AbstractController as PublicController;
use Src\Controller\Cabinet;
use Core\Facade\Template;
use Core\Service\Translation;
use Core\Service\Registry;
use Src\Helpers\Form;

abstract class AbstractController extends PublicController
{
    //protected $breadcrumbs['/'.Locale::getLocale().'/cabinet/'] = ' ';
    public function bootProcess()
    {
        $this->middleware(($this->auth = new Cabinet));
    }

    protected function bootDispatch()
    {
        Template::defaults([
            'alerts' => Registry::pull('session.alerts'),
            'Form' => new Form(),
            'path' => [
                'controller' => $this->path(),
                'uri' => $this->path(Registry::get('http.request.path'))
            ],
            'cabinet_menu' => [
                [
                    'url' => ($url = '/'.Locale::getLocale().'/cabinet/profile'),
                    'title' => Translation::of('Data of company'),
                    'is_active' => strstr($_SERVER['REQUEST_URI'], $url)
                ],
                [
                    'url' => ($url = '/'.Locale::getLocale().'/cabinet/products/add'),
                    'title' => Translation::of('Add products'),
                    'is_active' => strstr($_SERVER['REQUEST_URI'], $url)
                ],
                [
                    'url' => ($url = '/'.Locale::getLocale().'/cabinet/products/list'),
                    'title' => Translation::of('Your products'),
                    'is_active' => strstr($_SERVER['REQUEST_URI'], $url)
                ],

                [
                    'url' => ($url = '/'.Locale::getLocale().'/cabinet/news/add'),
                    'title' => Translation::of('Add news'),
                    'is_active' => strstr($_SERVER['REQUEST_URI'], $url)
                ],
                [
                    'url' => ($url = '/'.Locale::getLocale().'/cabinet/logout'),
                    'title' => Translation::of('Logout'),
                    'is_active' => strstr($_SERVER['REQUEST_URI'], $url)
                ],  
            ],
            'user'=>$this->auth->user()
        ]);
    }
}
?>