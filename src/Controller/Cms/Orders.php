<?php namespace Src\Controller\Cms;

use Core\Middleware\CsrfProtection;
use Core\Service\Form;
use Core\Service\Locale;
use Core\Service\Registry;
use Core\Middleware\Controller;
use Core\Facade\Template;
use Core\Model\Type\Attribute;
use Core\Service\Translation;
use Core\Service\Validator;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Src\Controller\Cms;
use Src\Model\Tariff;
use Zend\Db\Sql\Select;
use Core\Model;
use Core\Collection;
use Core\Gateway;
use Core\Gateway\Subjects;

class Orders extends Controller
{
    protected $auth;

    protected function bootProcess()
    {
        $this->middleware(($this->auth = new Cms));

    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $user_id = Registry::get('session.auth.cms.id');
        $user = Subjects::of('User')->select(['id'=>$user_id])->first();
        if(!$user->root && !$user->manager) return $this->redirect('/');

        return parent::process($request, $delegate);
    }

    protected function bootRouting()
    {
        $this->GET('/', 'getOrders');
        $this->GET('/edit/{id:\d+}', 'editPage');
        $this->POST('/edit/{id:\d+}', 'edit');
        $this->POST('/delete', 'delete');
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

    public function delete() {
        $typeName = 'Order';
        /** @var Model\Subject $subject */
        if(($subjectsIds = Registry::get('http.request.body.subjects')) && !!($subjects = Gateway\Subjects::of($typeName)->select(['id' => $subjectsIds])->get()) && $subjects->count()) {

            $subjects->each(function ($subject) {
                $subject->delete();
            });

            return $this->text('ok');
        }

        return $this->response(404);
    }

    public function edit($id) {

         $typeName = 'Order';
         $path = 'Order';

        if (preg_match("/^\w+$/u", $path)) {
            $typeName = $path;
        }

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

        Registry::set('session.alerts.success', Translation::of('Updated successfully', 'cms/settings'));

        return $this->redirect($this->path(Registry::get('http.request.path')));
    }

    public function editPage($id) {
        $typeName = 'Order';
        $path = 'Order';
         $isRelation = true;

        if (preg_match("/^\w+$/u", $path)) {

            $typeName = $path;

            $isRelation = false;
        }

        /** @var Model\Type $type */
        if (!$typeName || !($type = Registry::get('types')->get($typeName))) {
            return $this->response(404);
        }

        if (!$id || !($subject = Gateway\Subjects::of($type['name'])->find($id))) {
            return $this->response(404);
        }

        if ($isRelation) {// relation subject create page

            $relationsChain = $this->parseRelationsChain($path);

            $current = end($relationsChain);

            /** @var Attribute\Relation\DirectRelation $relation */
            $relation = $current['relation'];

            /** @var Model\Subject $parent */
            $parent = $current['subject'];

            if (!$parent->getRelations()->hasRelation($current['relation']['name'])) {
                return $this->response(404);
            }

            if ($relation->isDirectRelation() && $relation->isFactory() && !empty(($factoryType = $parent->offsetGet($relation['name']))) && $factoryType != $type['name']) {
                return $this->response(500);
            }
        }

        return $this->html(Template::render('/cms/orders/edit', [
            'type' => $type,
            'form' => Form::of($type->name)->setSubject($subject),
            'relationsChain' => !empty($relationsChain) ? $relationsChain : [],
        ]));
    }

    public function getOrders() {
        $locale = Locale::getLocale();
        $query = Registry::get('http.request.query');
        $path = 'Order';
        $typeName = null;
        $isRelation = true;

        if (preg_match("/^\w+$/u", $path)) {
            $typeName = $path;
            $isRelation = false;
        }

        if ($isRelation) {
            $relationsChain = $this->parseRelationsChain($path);
            $current = end($relationsChain);

            /** @var Model\Subject $parent */
            $parent = $current['subject'];

            if (!$parent->getRelations()->hasRelation($current['relation']['name'])) {
                return $this->response(404);
            }


            /** @var Attribute\Relation\DirectRelation $relation */
            $relation = $current['relation'];

            $targetType = null;

            if ($relation->isDirectRelation() && $relation->isFactory()) {

                if (!empty(($targetTypeName = $parent->offsetGet($relation['name'])))) {
                    $targetType = Registry::get('types')->get($targetTypeName);
                }

            } else {
                $targetType = Registry::get('types')->get($relation['settings']['is']);
            }
            $type_properties = $targetType ? $targetType->getProperties() : [];
            /** @var Model\Type $targetType */
            /** @var Collection\SubjectsCollection $subjects */
            $subjects = $targetType ? $parent->getRelations()->getRelationService($current['relation']['name'])->select(function (Select $select) use ($targetType, $query, $type_properties, $locale) {
                if(isset($query['filter']) && is_array($query['filter'])) {
                    foreach($query['filter'] as $name=>$val) {
                        if(empty($val)) continue;
                        $suffics = '';
                        if(intval($type_properties[$name]['settings']['translatable'])) {
                            $suffics = "_".$locale."_";
                        }
                        $lowVal = mb_strtolower($val);
                        if($lowVal == 'да') {
                            $select->where([$suffics.$name=>1]);
                        } elseif($lowVal == 'нет') {
                            $select->where([$suffics.$name=>0]);
                        } elseif($name == 'id') {
                            $select->where(['id'=>$val]);
                        } else {
                            $select->where([$suffics.$name." LIKE ?"=>'%'.$val.'%']);
                        }
                    }
                }
                if ($targetType->getProperties()->has('sort')) {
                    $select->order('sort');
                }
                $select->order($targetType->subjectsTable('id'));

            })->paginate(100) : [];


            $canCreateSubject = true;

            if ($relation->isBackRelation() || ($relation->isSingle() && !empty($subjects) && $subjects->count())) {
                $canCreateSubject = false;
            }

            $data = [
                'query' => $query,
                'type' => $targetType,
                'relationsChain' => $relationsChain,
                'type_properties' => $type_properties,
                'canCreateSubject' => $canCreateSubject,
                'parent' => $parent,
                'relation' => $relation,
                'subjects' => $subjects,
                'sortable' => $targetType && $targetType->isSortable() && $subjects->count()>1 && $subjects->pagination('maxPages')<2,
                'relationsCounts' => $targetType && $targetType->getDirectRelations()->count() && $subjects && $subjects->count() ? Gateway\Relations::of($targetType->name)->getCounts($subjects->pluck('id')->toArray()) : []
            ];

        } else {

            /** @var Model\Type $type */
            if (!($type = Registry::get('types')->get($typeName)) || $type->is_hidden) {
                return $this->response(404);
            }
            $type_properties = $type->getProperties();
            /** @var Collection\SubjectsCollection $subjects */
            $subjects = Gateway\Subjects::of($type['name'])->select(function (Select $select) use ($type, $query, $type_properties, $locale) {
                //$select->where(['is_related' => 0]);
                if(isset($query['filter']) && is_array($query['filter'])) {
                    foreach($query['filter'] as $name=>$val) {
                        if(empty($val)) continue;
                        $suffics = '';
                        if(intval($type_properties[$name]['settings']['translatable'])) {
                            $suffics = "_".$locale."_";
                        }
                        $lowVal = mb_strtolower($val);
                        if($lowVal == 'да') {
                            $select->where([$suffics.$name=>1]);
                        } elseif($lowVal == 'нет') {
                            $select->where([$suffics.$name=>0]);
                        } elseif($name == 'id') {
                            $select->where(['id'=>$val]);
                        } else {
                            $select->where([$suffics.$name." LIKE ?"=>'%'.$val.'%']);
                        }
                    }
                }
                if ($type->getProperties()->has('sort')) {
                    $select->order('sort');
                }

                $select->order('id');
            })->paginate(100);

            $data = [
                'query' => $query,
                'type' => $type,
                'type_properties' => $type_properties,
                'subjects' => $subjects,
                'sortable' => $type->isSortable() && $subjects->count()>1 && $subjects->pagination('maxPages')<2,
                'relationsCounts' => $subjects->count() && $type->getDirectRelations()->count() ? Gateway\Relations::of($type['name'])->getCounts($subjects->pluck('id')->toArray()) : []
            ];
        }

        return $this->html(Template::render('/cms/orders/orders', $data));
    }


}
