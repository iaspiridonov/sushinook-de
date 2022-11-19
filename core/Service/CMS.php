<?php
/**
 * Created by PhpStorm.
 * User: Artmedia1
 * Date: 06.06.2018
 * Time: 9:59
 */

namespace Core\Service;


use Core\Facade\Template;

class CMS
{
    public function editor($subject, $params = ['edit','add','remove']) {
        if($this->is_admin()) {
            $html = Template::render('cms/editor/editor',['subject'=>$subject]);
            return new \Twig_Markup($html,'UTF-8');
        } else {
            return '';
        }
    }

    public function is_admin() {
        return Registry::get('session.auth',false);
    }
}