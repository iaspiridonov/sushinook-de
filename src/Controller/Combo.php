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
class Combo extends AbstractController
{
    public function bootRouting()
    {
        $this->GET('/{id:\d+}[-{name:[a-z\d\-]+}]', 'comboPage');
    }

    public function comboPage($id) {
    	$combo = Subjects::of('Combo')->find($id);
        $seo = $combo->seo;
        return $this->html(Template::render('src/combo/page',['combo'=>$combo,'seo'=>$seo]));
    }
}