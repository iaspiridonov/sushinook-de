<?php namespace Src\Controller;

use Core\Facade\App;
use Core\Middleware\Controller;
use Core\Facade\Template;
use Core\Gateway\Subjects;
use Core\Model\Subject;
use Core\Service\Locale;
use Core\Service\Registry;
use Src\Controller\Cms;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Sql;
use Zend\Diactoros\Response\HtmlResponse;

class Page extends AbstractController
{
    public function bootRouting()
    {
        $this->GET('/{id:\d+}[-{name:[a-z\d\-]+}.html]', 'viewPage');
    }

    public function viewPage($id, $name = null)
    {
        if (!($page = Subjects::of('Page')->find($id))) {
            return $this->response(404);
        }

        $breadcrumbs[''] = $page->title;

        $seo = $page->seo;

        if($page->id == 24){
            $seo = Subjects::of('Seo')->find(12);
        }

        return $this->html(Template::render('src/page/page', [
            'page' => $page,
            'breadcrumbs'=>$breadcrumbs,
            'seo'=>$seo
        ]));
    }
}
