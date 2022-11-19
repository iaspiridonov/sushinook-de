<?php
namespace Src\Controller\Cms;
use Core\Middleware\Controller;
use Core\Service\Form;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Src\Controller\Cms;
use Core\Facade\Template;
use Core\Service\Locale;
use Core\Service\Registry;
use Core\Gateway\Subjects;
use Src\Model;
use Core\Gateway;

class Editor extends Controller {
    protected $auth;

    protected function bootProcess()
    {
        $this->middleware(($this->auth = new Cms));
    }

    protected function bootRouting()
    {
        $this->GET('/edit/{type:\w+}/{id:\d+}', 'editPage');
        $this->POST('/edit/{type:\w+}/{id:\d+}','edit');
        $this->POST('/remove/{type:\w+}/{id:\d+}','remove');

        $this->GET('/add/{type:\w+}', 'addPage');
        $this->POST('/add/{relation:\w+}/{type:\w+}/{relationType:\w+}/{id:\d+}', 'add');
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

    public function remove($typeName, $id) {
        $subject = Subjects::of($typeName)->find($id)->delete();

        return $this->html('lol');
    }

    public function addPage($typeName) {
        /** @var Model\Type $type */
        if (!$typeName || !($type = Registry::get('types')->get($typeName))) {
            return $this->response(404);
        }

        return $this->html(Template::render('/cms/editor/edit', [
            'type' => $type,
            'form' => Form::of($type->name),
        ]));
    }

    public function add($relationName, $relationTypeName, $typeName, $id) {
        if (!$typeName || !($type = Registry::get('types')->get($relationTypeName))) {
            return $this->response(404);
        }

        if (!($form = Form::of($type->name))->validate()) {
            return $this->redirect($this->path(Registry::get('http.request.path')));
        }

            /** @var Model\Subject $parent */
            $parent = Subjects::of($typeName)->find($id);

            if (!$parent->getRelations()->hasRelation($relationName)) {
                return $this->response(404);
            }
        $relation = Registry::get('types')->get($typeName)->getRelations()->get($relationName);
        if ($relation->isDirectRelation() && $relation->isFactory()) {

            if (empty(($factoryType = $parent->offsetGet($relationName)))) {

                $parent->offsetSet($relationName, $typeName);
                $parent->save();

            } elseif ($factoryType != $type['name']) {

                return $this->response(500);
            }
        }

            $subject = $form->process();
            $parent->getRelations()->getRelationService($relationName)->save($subject);
            return $this->html('kek');
    }

    public function edit($typeName, $id) {

        if (!$typeName || !($type = Registry::get('types')->get($typeName))) {
            return $this->response(404);
        }

        if (!$id || !($subject = Gateway\Subjects::of($type['name'])->find($id))) {
            return $this->response(404);
        }

        if (!($form = Form::of($type->name))->setSubject($subject)->validate()) {
            return $this->redirect($this->path(Registry::get('http.request.path')));
        }

        $form->process();

        return $this->html('завершено');
    }

    public function editPage($typeName, $id) {
        $isRelation = true;
        /** @var Model\Type $type */
        if (!$typeName || !($type = Registry::get('types')->get($typeName))) {
            return $this->response(404);
        }

        if (!$id || !($subject = Gateway\Subjects::of($type['name'])->find($id))) {
            return $this->response(404);
        }

        return $this->html(Template::render('/cms/editor/edit', [
            'type' => $type,
            'form' => Form::of($type->name)->setSubject($subject),
        ]));
    }


}

?>