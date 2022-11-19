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

class Settings extends Controller
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
        if(!$user->root) return $this->redirect('/');

        return parent::process($request, $delegate);
    }

    protected function bootRouting()
    {
        $this->GET('/', function () {
            return $this->redirect($this->path('/types'));
        });

        $this->GET('/types', 'typesPage');
        $this->GET('/type/create', 'createTypePage');
        $this->POST('/type/create', 'createType');

        $this->POST('/type/{name:\w+}/sort-attributes', 'sortTypeAttributes');

        $this->GET('/type/{name:\w+}/edit[/attribute-edit-{id:\d+}]', 'editTypePage');
        $this->POST('/type/{name:\w+}/edit[/attribute-edit-{id:\d+}]', 'editType');

        $this->GET('/delete-type/{id:\d+}', 'deleteType');
        $this->GET('/delete-type-attribute/{id:\d+}', 'deleteTypeAttribute');
        $this->POST('/delete-subjects/{type:\w+}', 'deleteSubjects');

        $this->POST('/unbind-subjects/{type:\w+}-{parentId:\d+}-{relation:\w+}', 'unbindSubjects');

        $this->GET('/subjects/{path:.*}/create[:{type:\w+}]', 'createSubjectPage');
        $this->POST('/subjects/{path:.*}/create[:{type:\w+}]', 'createSubject');

        $this->GET('/subjects/{path:.*}/edit:{type:\w+}:{id:\d+}', 'editSubjectPage');
        $this->POST('/subjects/{path:.*}/edit:{type:\w+}:{id:\d+}', 'editSubject');

        $this->GET('/subjects/{path:.*}/bind:{type:\w+}', 'bindSubjectPage');
        $this->POST('/subjects/{path:.*}/bind:{type:\w+}', 'bindSubject');

        $this->POST('/subjects/{path:.*}/sort', 'typeSubjectsSort');
        $this->GET('/subjects/{path:.*}', 'typeSubjectsPage');

        $this->GET('/tariff', 'showTariffPage');
        $this->POST('/tariff', 'addTariff');
        $this->POST('/tariff/edit', 'editTariff');
        $this->POST('/tariff/remove', 'removeTariff');
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

    public function removeTariff() {
        $requestBody = Registry::get('http.request.body');
        $id = $requestBody['id'];
        Subjects::of('Tariff')->delete(['id'=>$id]);

        return $this->html('1');
    }

    public function editTariff() {
        $requestBody = Registry::get('http.request.body');
        $id = $requestBody['id'];
        $cost1 = $requestBody['cost1'];
        $cost2 = $requestBody['cost2'];
        $cost3 = $requestBody['cost3'];
        $cost4 = $requestBody['cost4'];
        $cost5 = $requestBody['cost5'];
        $delivery_time = $requestBody['delivery_time'];

        Subjects
            ::of('Tariff')
            ->update([
                'cost1'=>$cost1,
                'cost2'=>$cost2,
                'cost3'=>$cost3,
                'cost4'=>$cost4,
                'cost5'=>$cost5,
                'delivery_time'=>$delivery_time
                ] ,
                [
                    'id'=>$id
                ]);
        return $this->html(' cost2 - '.$cost2);
    }

    public function addTariff() {
        $request = Registry::get('http.request.query');
        $requestBody = Registry::get('http.request.body');
        $countries = Subjects::of('Country')->get();
        if(isset($request['country'])) {
            $activeCountry = $request['country'];
        } else {
            $activeCountry = $countries->first()->name;
            //$cities = Subjects::of('City');
        }
        $currentCountry = Subjects::of('Country')->select(['name'=>$activeCountry])->with(['cities'])->first();
        $cities = $currentCountry->cities;
        if(isset($request['city'])) {
            $activeCity = $request['city'];
        } else {
            $activeCity = $cities->first()->name;
            //$cities = Subjects::of('City');
        }
        $to = $requestBody['to'];
        $cost1 = $requestBody['cost1'];
        $cost2 = $requestBody['cost2'];
        $cost3 = $requestBody['cost3'];
        $cost4 = $requestBody['cost4'];
        $cost5 = $requestBody['cost5'];
        $delivery_time = $requestBody['delivery_time'];


        ($order = new Tariff([
            'from_city' => $activeCity,
            'to_city' => $to,
            'cost1' => $cost1,
            'cost2' => $cost2,
            'cost3' => $cost3,
            'cost4' => $cost4,
            'cost5' => $cost5,
            'delivery_time' => $delivery_time
        ]))->save();

        return $this->redirect($this->path('/tariff?country='.$activeCountry.'&city='.$activeCity));
    }

    public function showTariffPage() {
        $request = Registry::get('http.request.query');
        $countries = Subjects::of('Country')->get();
        if(isset($request['country'])) {
            $activeCountry = $request['country'];
        } else {
            $activeCountry = $countries->first()->name;
            //$cities = Subjects::of('City');
        }
        $currentCountry = Subjects::of('Country')->select(['name'=>$activeCountry])->with(['cities'])->first();
        $cities = $currentCountry->cities;

        if(isset($request['city'])) {
            $activeCity = $request['city'];
        } else {
            $activeCity = $cities->first()->name;
            //$cities = Subjects::of('City');
        }

        $tariffs = Subjects::of('Tariff')->select(['from_city=? OR to_city=?'=>[$activeCity,$activeCity]])->get();

        return $this->html(Template::render('/cms/module/settings/tariff/index',
            ['countries'=>$countries,
             'activeCountry'=>$activeCountry,
             'activeCity'=>$activeCity,
             'cities'=>$cities,
             'tariffs'=>$tariffs]));
    }

    public function typesPage()
    {
        return $this->html(Template::render('/cms/module/settings/types', [
            'types' => ($types = Registry::get('types')->filter(function ($item) {
                return $item['is_hidden'] == 0;
            })) ? $types->sortBy('name') : [],
            'related' => ($types = Registry::get('types')->filter(function ($item) {
                return $item['is_hidden'] == 1;
            })) ? $types->sortBy('name') : [],

        ]));
    }

    public function sortTypeAttributes($typeName)
    {
        /** @var Model\Type $type */
        if (!($type = Registry::get('types')->get($typeName))) {
            return $this->response(404);
        }

        $index = 0;

        if (($sort = Registry::get('http.request.body.attribute'))) {
            foreach ($sort as $attributeId) {
                if(($attributeName = $type->getAttributes()->search(function ($item) use ($attributeId) {
                    return $item->id == $attributeId;
                })) === false) continue;

                $attribute = $type->getAttributes()->get($attributeName);

                $settings = $attribute->settings;

                $settings['interface']['sort'] = ($index+=100);

                $attribute->settings = $settings;

                $attribute->save();
            }
        }

        return $this->text('ok');
    }

    public function createTypePage()
    {
        return $this->html(Template::render('/cms/module/settings/type/create', [
            'errors' => Registry::pull('session.errors'),
            'old' => Registry::pull('session.old')
        ]));
    }

    public function createType()
    {
        $rules = [
            'name' => [
                new Validator\NotEmpty(Translation::of("Name can't be empty", 'cms/settings')),
                new Validator\Regex('/^[a-zA-Z]\w+$/', Translation::of('Only latin letters, _ and digits, without spaces', 'cms/settings')),
                new Validator\Lambda(function ($value) {
                    return !Registry::get('types')->has($value);
                }, Translation::of('Type with such name already exists', 'cms/settings'))
            ],
            'label' => new Validator\NotEmpty(Translation::of("Label can't be empty", 'cms/settings')),
            'is_hidden' => new Validator\Mutator(function ($value) {
                return isset($value) && in_array($value, [0, 1]) ? $value : 0;
            })
        ];

        $old = Registry::get('http.request.body');

        if (!($validator = new Validator($rules, $old))->isValid()) {

            Registry::set('session.errors', $validator->getErrors());
            Registry::set('session.old', $old);

            return $this->redirect(Registry::get('http.controller.path').'/type/create');
        }

        $data = $validator->getData();

        /** @var Model\Type $type */
        ($type = (new Model\Type)->populate($data))->save();

        Registry::set('session.alerts.success', sprintf(Translation::of('Type %s was created successfully.', 'cms/settings'), '<strong>'.$type->name.'</strong>'));

        return $this->redirect($this->path('/type/'.$type->name.'/edit'));
    }

    public function editTypePage($typeName, $editAttributeId = null)
    {
        if (!($type = Registry::get('types')->get($typeName))) {
            return $this->response(404);
        }

        $possibleBackRelations = new Collection\Type\AttributesCollection;

        Registry::get('types')->each(function ($targetType) use ($possibleBackRelations, $type) {

            /** @var Model\Type $targetType */
            $targetType->getDirectRelations()->each(function ($relation, $key) use ($possibleBackRelations, $type) {

                /** @var Model\Type\Attribute\Relation $relation */
                if ($relation->is($type['name'])) {
                    $possibleBackRelations->push($relation);
                }
            });
        });

        $editAttribute = null;

        if ($editAttributeId) {

            if (!($editAttribute = (new Gateway\TypesAttributes)->find($editAttributeId))) {
                Registry::set('session.alerts.danger', Translation::of('There is no attribute to edit!', 'cms/settings'));
            }
        }

        $allowedKinds = function () {

            $static = [];

            foreach (Attribute\Property::KINDS as $PROPERTY) {
                $static[$PROPERTY] = $PROPERTY;
            }

            $others = [
                Attribute\Relation\DirectRelation::KIND => Attribute\Relation\DirectRelation::KIND,
                Attribute\Relation\BackRelation::KIND => Attribute\Relation\BackRelation::KIND
            ];

            return [
                'Static inputs' => $static,
                'Relations' => $others
            ];
        };

        return $this->html(Template::render('/cms/module/settings/type/edit', [
            'type' => $type,
            'allowedKinds' => $allowedKinds(),
            'possibleBackRelations' => $possibleBackRelations,
            'errors' => Registry::pull('session.errors'),
            'old' => Registry::pull('session.old'),
            'editAttribute' => $editAttribute
        ]));
    }

    public function editType($typeName, $editAttributeId = null)
    {
        /** @var Model\Type $type */
        if (!($type = Registry::get('types')->get($typeName))) {
            return $this->response(404);
        }

        $errors = [];

        $editAttribute = null;

        if ($editAttributeId) {

            if (!($editAttribute = (new Gateway\TypesAttributes)->find($editAttributeId))) {
                Registry::set('session.alerts.danger', Translation::of('There is no attribute to edit!', 'cms/settings'));
                return $this->redirect($this->path(Registry::get('http.request.path')));
            }
        }

        $rules = [

            'name' => [
                new Validator\NotEmpty(Translation::of("Name can't be empty", 'cms/settings')),

                new Validator\Regex('/^[a-zA-Z]\w+$/', Translation::of('Only latin letters, _ and digits, without spaces', 'cms/settings')),

                new Validator\Lambda(function ($value) use ($type) {

                    return $value == $type->name || !Registry::get('types')->has($value);

                }, Translation::of('Type with such name already exists', 'cms/settings'))
            ],

            'label' => new Validator\NotEmpty(Translation::of("Label can't be empty", 'cms/settings')),

            'is_hidden' => new Validator\Mutator(function ($value) {
                return isset($value) && in_array($value, [0, 1]) ? $value : 0;
            })
        ];

        if (!($validator = new Validator($rules, Registry::get('http.request.body')))->isValid()) {
            $errors = $validator->getErrors();
        }

        $old = $data = $validator->getData();

        #CHECH ATTRIBUTE IF CAME, EDIT OR NEW
        if (($attribute = Registry::get('http.request.body.attribute'))) {

            $rules = [
                'kind' => new Validator\Lambda(function ($value) use ($type) {

                    return in_array($value, array_merge(Attribute\Property::KINDS, [Attribute\Relation\DirectRelation::KIND, Attribute\Relation\BackRelation::KIND]));

                }, "Kind not valid"),

                'name' => [
                    new Validator\NotEmpty(Translation::of("Name can't be empty", 'cms/settings')),
                    new Validator\Regex('/^[a-zA-Z]\w+$/', Translation::of('Only latin letters, digits without spaces', 'cms/settings')),
                    new Validator\Lambda(function ($value) use ($type, $editAttribute) {

                        return (($editAttribute && $value == $editAttribute->name) || !$type->getAttributes()->has($value));

                    }, sprintf(Translation::of('Type "%s" already has "%s" attribute', 'cms/settings'), $type->name, $attribute['name']))
                ],

                'label' => new Validator\NotEmpty(Translation::of("Label can't be empty", 'cms/settings'))
            ];

            if (!($validator = new Validator($rules, $attribute))->isValid()) {
                $errors['attribute'] = $validator->getErrors();
            }

            $settings = [];
            $interface = [];

            if (in_array($attribute['kind'],Attribute\Property::KINDS)) {

                $settings['translatable'] = !isset($attribute['translatable']) || !in_array($attribute['translatable'], [0,1]) || count(Locale::getLocales())<2 ? 0 : $attribute['translatable'];// settings set
                $interface['required'] = !isset($attribute['required']) || !in_array($attribute['required'], [0,1]) ? 0 : $attribute['required'];// settings set
                $interface['visible'] = !isset($attribute['visible']) || !in_array($attribute['visible'], [0,1]) ? 0 : $attribute['visible'];// settings set

                if (in_array($attribute['kind'],Attribute\Property::MULTIVALUE_PROPERTIES)) {
                    if (!(new Validator\NotEmpty())->isValid($attribute['values'])) {
                        $errors['attribute']['values'] = Translation::of("Can't be empty", 'cms/settings');
                    } else {

                        $values = explode("\n", trim($attribute['values']));

                        $settings['values'] = [];

                        foreach ($values as $hash) {

                            $hash = trim($hash);

                            if (!preg_match('/^\S+:.+$/ui', $hash)) {
                                $errors['attribute']['values'] = Translation::of("Wrong data format. Need Key:Value style, with no spaces in Key (just digits or letters)", 'cms/settings');
                                break;
                            }

                            list($key, $value) = explode(':', $hash);

                            $settings['values'][$key] = $value;
                        }
                    }
                }

            } elseif (in_array($attribute['kind'], [Attribute\Relation\DirectRelation::KIND, Attribute\Relation\BackRelation::KIND])) {

                if ($attribute['kind'] == Attribute\Relation\DirectRelation::KIND) {

                    if (empty($attribute['relation']) || !is_array($attribute['relation'])) {

                        $errors['attribute']['relation'] = Translation::of("Can't be empty", 'cms/settings');

                    } else {

                        $typesNames = Registry::get('types')->keys()->toArray();

                        foreach ($attribute['relation'] as $typeName) {

                            if (!in_array($typeName, $typesNames)) {
                                $errors['attribute']['relation'] = Translation::of("Not valid type", 'cms/settings');
                                break;
                            }
                        }

                        $settings['is'] = count($attribute['relation']) > 1 ? $attribute['relation'] : current($attribute['relation']); //settings set

                        $settings['strong'] = !isset($attribute['strong']) || !in_array($attribute['strong'], [0,1]) ? 1 : $attribute['strong'];
                        $interface['internal'] = !isset($attribute['internal']) || !in_array($attribute['internal'], [0,1]) ? 0 : $attribute['internal'];

                        $settings['single'] = 1;
                        if (!$interface['internal'] && !is_array($settings['is']) && isset($attribute['single']) && in_array($attribute['single'], [0, 1])) {
                            $settings['single'] = (int)$attribute['single'];
                        }
                    }

                } elseif ($attribute['kind'] == Attribute\Relation\BackRelation::KIND) {

                    /** @var Attribute\Relation\DirectRelation $relation */
                    if (!empty($attribute['back_relation']) && ($relation = (new Gateway\TypesAttributes)->find($attribute['back_relation'])) && $relation->isDirectRelation() && $relation->is($type['name'])) {

                        $settings['is'] = $relation['type'];//settings set
                        $settings['relation'] = $relation['name'];//settings set
                    } else {
                        $errors['attribute']['back_relation'] = Translation::of("Not valid relation", 'cms/settings');
                    }

                    $settings['single'] = 1;
                    if (isset($attribute['single']) && in_array($attribute['single'], [0, 1])) {
                        $settings['single'] = (int)$attribute['single'];
                    }
                }
            }

            $interface['sort'] = !empty($editAttribute->settings['interface']['sort']) ? $editAttribute->settings['interface']['sort'] : time();// settings set
            $old['attribute'] = $attribute;
        }

        if ($errors) {

            Registry::set('session.errors', $errors);
            Registry::set('session.old', $old);

            return $this->redirect($this->path(Registry::get('http.request.path')));
        }

        $type->merge($data)->save();

    #ATTRIBUTE SAVE
        if ($attribute) {

            if ($interface) {
                $settings['interface'] = $interface;
            }

            $newAttribute = [
                'type' => $type['name'],
                'name' => $attribute['name'],
                'label' => $attribute['label'],
                'kind' => $attribute['kind'],
                'settings' => $settings
            ];

        #EDITED ATTRIBUTE
            if ($editAttribute) {
                $editAttribute->merge($newAttribute)->save();

        #ADD NEW ATTRIBUTE
            } else {
                (new Attribute\Factory)->populate($newAttribute)->save();
            }
        }

        Registry::set('session.alerts.success', Translation::of('Saved successfully', 'cms/settings'));

        return $this->redirect($this->path('/type/'.$type['name'].'/edit'));
    }

    public function deleteType($id)
    {
        /** @var Model\Type $type */
        if(!!($type = (new Gateway\Types)->find($id))) {
            $name = $type->name;
            $label = $type->label;

            $type->delete();

            Registry::set('session.alerts.success', sprintf(Translation::of('Type %s was deleted successfully', 'cms/settings'), '<strong>"'.$label.' #'.$name.'"</strong>'));

            return $this->redirect($_SERVER['HTTP_REFERER']);
        }

        return $this->response(404);
    }

    public function deleteTypeAttribute($id)
    {
        /** @var Attribute $attribute */
        if(!!($attribute = (new Gateway\TypesAttributes)->find($id))) {
            $type = $attribute['type'];
            $name = $attribute['name'];
            $label = $attribute['label'];

            $attribute->delete();

            Registry::set('session.alerts.success', sprintf(Translation::of('Attribute %s was deleted successfully', 'cms/settings'), '"<strong>'.$label.' #'.$name.'</strong>"'));

            return $this->redirect($this->path('/type/'.$type.'/edit'));
        }

        return $this->response(404);
    }

    public function deleteSubjects($typeName)
    {
        /** @var Model\Subject $subject */
        if(($subjectsIds = Registry::get('http.request.body.subjects')) && !!($subjects = Gateway\Subjects::of($typeName)->select(['id' => $subjectsIds])->get()) && $subjects->count()) {

            $subjects->each(function ($subject) {
                $subject->delete();
            });

            return $this->text('ok');
        }

        return $this->response(404);
    }

    private function parseRelationsChain($relations)
    {
        if (!preg_match_all("/(?P<type>\w+)\-(?P<subject_id>\d+)-(?P<relation>\w+)/", $relations, $matches, PREG_SET_ORDER)) {
            throw new \HttpInvalidParamException('Wrong relations url!');
        }

        $relationsChain = [];

        if ($matches) {

            foreach ($matches as $match) {

                $data = [];

                /** @var Model\Type $chainType */
                if (!($chainType = Registry::get('types')->get($match['type']))) {
                    throw new \Exception('Wrong type in chain');
                }
                $data['type'] = $chainType;

                if(!($relation = $chainType->getRelations()->get($match['relation']))) {
                    throw new \Exception('Wrong type relation in chain');
                }
                $data['relation'] = $relation;

                if (!($subject = Gateway\Subjects::of($chainType->name)->find($match['subject_id']))) {
                    throw new \Exception('Wrong subject ID');
                }
                $data['subject'] = $subject;

                $relationsChain []= $data;
            }
        }

        return $relationsChain;
    }

    public function typeSubjectsPage($path)
    {
    	$locale = Locale::getLocale();
        $typeName = null;
        $isRelation = true;
        $query = Registry::get('http.request.query');
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

            })->paginate(150) : [];


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
                'sortable' => $targetType && $targetType->isSortable() && $subjects->count()>1, //&& $subjects->pagination('maxPages')<1
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
                $select->where(['is_related' => 0]);
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
            })->paginate();

            $data = [
            	'query' => $query,
                'type' => $type,
                'type_properties' => $type_properties,
                'subjects' => $subjects,
                'sortable' => $type->isSortable() && $subjects->count()>1 && $subjects->pagination('maxPages')<2,
                'relationsCounts' => $subjects->count() && $type->getDirectRelations()->count() ? Gateway\Relations::of($type['name'])->getCounts($subjects->pluck('id')->toArray()) : []
            ];
        }

        return $this->html(Template::render('/cms/module/settings/subjects', $data));
    }

    public function typeSubjectsSort($path)
    {
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

            if (!$parent->getRelations()->hasRelation(($relation = $current['relation'])->name)) {
                return $this->response(404);
            }/** @var Attribute\Relation\DirectRelation $relation */

            $subjects = ($service = $parent->getRelations()->getRelationService($relation->name)) ? $service->get() : [];

        } else {

            /** @var Model\Type $type */
            if (!($type = Registry::get('types')->get($typeName))) {
                return $this->response(404);
            }

            $subjects = Gateway\Subjects::of($type->name)->select(function (Select $select) use ($type) {
                $select->where(['is_related' => 0]);
            })->get();

        }

        if ($subjects && $subjects->count()) {
            $index = 0;

            if (($sort = Registry::get('http.request.body.subject'))) {

                foreach ($sort as $subjectId) {

                    if (!($subject = $subjects->get($subjectId))) continue;

                    $subject->offsetSet('sort', ($index+=100));
                    $subject->save();
                }
            }
        }

        return $this->text('ok');
    }

    public function createSubjectPage($path, $typeName = null)
    {
        $isRelation = true;

        if (preg_match("/^\w+$/u", $path)) {

            $typeName = $path;

            $isRelation = false;
        }

        /** @var Model\Type $type */
        if (!$typeName || !($type = Registry::get('types')->get($typeName))) {
            return $this->response(404);
        }

        if ($isRelation) {// relation subject create page
            $relationsChain = $this->parseRelationsChain($path);
            $current = array_pop($relationsChain);

            /** @var Attribute\Relation $relation */
            $relation = $current['relation'];

            /** @var Model\Subject $parent */
            $parent = $current['subject'];

            if (!$parent->getRelations()->hasRelation($current['relation']['name'])) {
                return $this->response(404);
            }

            if ($relation->isDirectRelation()) {
                /** @var Attribute\Relation\DirectRelation $relation */
                if ($relation->isFactory() && !empty(($factoryType = $parent->offsetGet($relation['name']))) && $factoryType != $type['name']) {
                    return $this->response(500);
                }
            }

            $current['parent'] = $parent;
            $relationsChain[]= $current;
        }

        return $this->html(Template::render('/cms/module/settings/subjects/create', [
            'type' => $type,
            'form' => Form::of($type->name),
            'relationsChain' => !empty($relationsChain) ? $relationsChain : []
        ]));
    }

    public function createSubject($path, $typeName = null)
    {
        $isRelation = true;

        if (preg_match("/^\w+$/u", $path)) {
            $typeName = $path;
            $isRelation = false;
        }

        if (!$typeName || !($type = Registry::get('types')->get($typeName))) {
            return $this->response(404);
        }

        if (!($form = Form::of($type->name))->validate()) {
            return $this->redirect($this->path(Registry::get('http.request.path')));
        }

        if ($isRelation) {// relation subject create page
            $relationsChain = $this->parseRelationsChain($path);
            $current = end($relationsChain);

            /** @var Attribute\Relation\DirectRelation $relation */
            $relation = $current['relation'];

            /** @var Model\Subject $parent */
            $parent = $current['subject'];

            if (!$parent->getRelations()->hasRelation($relation['name'])) {
                return $this->response(404);
            }

            if ($relation->isDirectRelation() && $relation->isFactory()) {

                if (empty(($factoryType = $parent->offsetGet($current['relation']['name'])))) {

                    $parent->offsetSet($current['relation']['name'], $type['name']);
                    $parent->save();

                } elseif ($factoryType != $type['name']) {

                    return $this->response(500);
                }
            }

            if ($relation->isBackRelation() || ($relation->isSingle() && $parent->getRelations()->getRelationService($relation['name'])->count())) {
                return $this->response(403);
            }

            $subject = $form->process();
            $parent->getRelations()->getRelationService($relation['name'])->save($subject);

        } else {
            $form->process();
        }

        Registry::set('session.alerts.success', Translation::of('Created successfully', 'cms/settings'));

        if ($isRelation && $relation->isSingle()) {
            return $this->redirect($this->path('/subjects/'.$path));
        }

        return $this->redirect($this->path(Registry::get('http.request.path')));
    }

    public function editSubjectPage($path, $typeName = null, $id = null)
    {
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

        return $this->html(Template::render('/cms/module/settings/subjects/edit', [
            'type' => $type,
            'form' => Form::of($type->name)->setSubject($subject),
            'relationsChain' => !empty($relationsChain) ? $relationsChain : [],
        ]));
    }

    public function editSubject($path, $typeName = null, $id = null)
    {
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

    public function bindSubjectPage($path, $typeName)
    {
        /** @var Model\Type $type */
        if (!$typeName || !($type = Registry::get('types')->get($typeName))) {
            return $this->response(404);
        }

        $relationsChain = $this->parseRelationsChain($path);
        $current = end($relationsChain);

        /** @var Model\Subject $parent */
        $parent = $current['subject'];

        if (!$parent->getRelations()->hasRelation(($relation = $current['relation'])->name)) {
            return $this->response(404);
        }
        /** @var Attribute\Relation\DirectRelation $relation */
        $targetType = null;

        if ($relation->isDirectRelation() && $relation->isFactory()) {

            if (!empty(($targetTypeName = $parent->offsetGet($relation['name'])))) {
                $targetType = Registry::get('types')->get($targetTypeName);
            }

        } else {
            $targetType = Registry::get('types')->get($relation['settings']['is']);
        }
        $type_properties = $targetType ? $targetType->getProperties() : [];
        $subjects = Gateway\Subjects::of($type->name)->select(function (Select $select) use ($type, $parent, $relation, $type_properties) {
            if ($parent->getType('name') == $type->name) {
                $select->where(['id <> ?' => $parent->id]);
            }

            if (($service = $parent->getRelations()->getRelationService($relation->name)) && $service->count()) {
                $select->where->notIn('id', $service->get()->pluck('id')->toArray());
            }

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
        })->paginate();

        $data = [
            'type' => $type,
            'type_properties' => $type_properties,
            'relationsChain' => $relationsChain,
            'relation' => $relation,
            'subjects' => $subjects,
            'relationsCounts' => $type->getDirectRelations()->count() && $subjects && $subjects->count() ? Gateway\Relations::of($type->name)->getCounts($subjects->pluck('id')->toArray()) : []
        ];

        return $this->html(Template::render('cms/module/settings/subjects/bind', $data));
    }

    public function bindSubject($path, $typeName)
    {
        /** @var Model\Type $type */
        if (!$typeName || !($type = Registry::get('types')->get($typeName))) {
            return $this->response(404);
        }

        if (!($bind = Registry::get('http.request.body.bind'))) {
            return $this->response(403);
        }

        $relationsChain = $this->parseRelationsChain($path);
        $current = end($relationsChain);

        /** @var Model\Subject $parent */
        $parent = $current['subject'];

        if (!$parent->getRelations()->hasRelation(($relation = $current['relation'])->name)) {
            return $this->response(404);
        }

        /** @var Attribute\Relation\DirectRelation $relation */

        $subjects = Gateway\Subjects::of($type->name)->select(function (Select $select) use ($type, $parent, $relation, $bind) {
            if ($parent->getType('name') == $type->name) {
                $select->where(['id <> ?' => $parent->id]);
            }

            if (($service = $parent->getRelations()->getRelationService($relation->name)) && $service->count()) {
                $select->where->notIn('id', $service->get()->pluck('id')->toArray());
            }

            $select->where(['id' => $bind]);
        })->toArray();

        if (count($subjects)) {
            if ($relation->isFactory()) {
                $parent->offsetSet($relation->name, $type->name)->save();
            }

            $parent->getRelations()->getRelationService($current['relation']['name'])->saveMany($subjects);
            Registry::set('session.alerts.success', Translation::of('Binded successfully', 'cms/settings'));
        } else {
            Registry::set('session.alerts.danger', Translation::of('Bind error, nothing to bind', 'cms/settings'));
        }

        return $this->redirect($this->path('/subjects/'.$path));
    }

    public function unbindSubjects($typeName, $parentId, $relationName)
    {
        /** @var Model\Subject\Relation $relation */
        if(($subjectsIds = Registry::get('http.request.body.subjects')) && !!($relations = Gateway\Relations::of($typeName)->select(['relation' => $relationName, 'subject_id' => $parentId, 'relation_subject_id' => $subjectsIds])->get()) && $relations->count()) {
            $relations->each(function ($relation) {
                $relation->delete();
            });
            Registry::set('session.alerts.success', Translation::of('Unbinded successfully', 'cms/settings'));
            return $this->text('ok');
        }

        return $this->response(404);
    }
}
