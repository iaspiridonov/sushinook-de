<?php namespace Core\Service;

use Core\Facade\Template;
use Core\Gateway\Subjects;
use Core\Model\Subject;
use Core\Model\Type;
use Core\Model\Type\Attribute;
use Zend\Diactoros\UploadedFile;

class Form
{
    protected $id;
    protected $prefix = [];
    /** @var  Type */
    protected $type;
    protected $properties;
    /** @var Subject */
    protected $subject;

    protected $validated;
    protected $errors;
    protected $old;

    public static function of($type, $prefix = [], $subject = null)
    {
        return new static($type, $prefix, $subject);
    }

    public function __construct($typeName, $prefix = [], $subject = null)
    {
        if (!($this->type = Registry::get('types')->get($typeName))) {
            throw new \Exception('There is no Type with name "'.$typeName.'"');
        }
        /** @var Subject $subject */
        if ($subject && ($this->subject = $subject)->getType('name') != $this->getType('name')) {
            throw new \Exception('Subject must be same type as Form');
        }


        $this->id = (($this->prefix = $prefix) ? join('-', $this->prefix).'-' : '').$this->getType('name').'-subject-form';
    }

    public function getType($attributeName = null)
    {
        if (!$this->type) {
            throw new \Exception('There is no type set');
        }

        return $attributeName ? $this->type->offsetGet($attributeName) : $this->type;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function getValidated()
    {
        return $this->validated;
    }

    public function getPrefixString()
    {
        return $this->prefix ? collect($this->prefix)->map(function($value){
            return '['.$value.']';
        })->implode("") : '';
    }

    public function except(array $exceptions)
    {
        $this->properties = $this->getType()->getAttributes()->filter(function (Attribute $item) use ($exceptions) {
            return (($item->isRelation() && $item->isDirectRelation() && $item->isInternal()) || $item->isProperty()) && !in_array($item->name, $exceptions);
        });

        return $this;
    }

    public function getProperties()
    {
        if (!$this->properties) {
            $this->properties = $this->getType()->getAttributes()->filter(function (Attribute $item) {
                return ($item->isRelation() && $item->isDirectRelation() && $item->isInternal()) || $item->isProperty();
            });
        }

        return $this->properties;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function errors()
    {
        if (!$this->errors) {
            $this->errors = Registry::pull('session.errors.'.$this->id);
        }

        return $this->errors;
    }

    public function old()
    {
        if (!$this->old) {
            $this->old = Registry::pull('session.old.'.$this->id);
        }

        return $this->old;
    }

    public function open($attributes = [])
    {
        $formAttributes = [
            'id' => $this->id,
            'method' => 'post',
            'enctype' => 'multipart/form-data'
        ];

        if ($attributes) {
            foreach ($attributes as $key => $value) {
                if ($value) {
                    $formAttributes[$key] = $value;
                } elseif (key_exists($key, $formAttributes)) {
                    unset($formAttributes[$key]);
                }
            }
        }

        $data = [
            'form' => $this,
            'formAttributes' => ($this->formAttributes = $formAttributes = collect($formAttributes)),
            'formAttributesString' => $formAttributes->map(function($value, $key){
                return $key.'="'.$value.'"';
            })->implode(" ")
        ];

        return Template::render('cms/module/settings/subjects/form/open', $data);
    }

    public function builder()
    {
        $data = [
            'form' => $this,
            'properties'=>$this->getProperties(),
            'errors' => $this->errors(),
            'old' => $this->old()
        ];

        if ($this->subject) {
            $data['edit'] = $this->subject;
        }

        return Template::render('cms/module/settings/subjects/form/builder.html.twig', $data);
    }

    public function close($withDefaultScripts = true)
    {
        return Template::render('cms/module/settings/subjects/form/close', ['form' => $this, 'formAttributes' => $this->formAttributes, 'withDefaultScripts' => $withDefaultScripts]);
    }

    public function validate($formSubjectInput = null)
    {
        if (!$this->getProperties()->count() || (!($formSubject = ($formSubjectInput ? $formSubjectInput : Registry::get('http.request.body.subject'))) && !Registry::get('http.request.files.subject'))) {
            return false;
        }

        //dump(Registry::get('http.request.body.subject'));

        $errors = [];
        $relationForms = [];

        $typeName = $formSubject ? key($formSubject) : null;
        $columns = $formSubject ? $formSubject[$typeName] : [];

        $cleanSubject = [];

        if (isset($columns['id'])) {
            $cleanSubject['id'] = $columns['id'];
        }

        $hasErrors = Registry::get('session.errors') ? true : false;

        $this->getProperties()->each(function ($attribute) use (&$formSubject, $typeName, $columns, &$cleanSubject, &$errors, &$relationForms, $hasErrors) {/** @var Attribute $attribute */

            if ($attribute->isRelation()) {/** @var Attribute\Relation $attribute */
                if ($attribute->isDirectRelation()) {/** @var Attribute\Relation\DirectRelation $attribute */
                    if (/*$attribute->isInternal() && */!empty($columns[$attribute->name])) {
                        $relationForms [] = [
                            'relationName' => $attribute->name,
                            'form' => new Form(key($columns[$attribute->name]), array_merge($this->prefix, [$this->getType('name'), $attribute->name])),
                            'arguments' => $columns[$attribute->name]
                        ];
                    }
                }

            } elseif ($attribute->isProperty()) {/** @var Attribute\Property $property */

                $property = $attribute;

                $values = [];

                if ($property->isTranslatable()) {/** @var Attribute\Property\TranslatableProperty $property */

                    foreach (Locale::getLocales() as $locale) {
                        $columnName = $property->getColumnName($locale);
                        $values[$columnName] = key_exists($columnName, $columns) ? $columns[$columnName] : null;
                    }

                } else {
                    $columnName = $property->getColumnName();
                    $values[$columnName] = key_exists($columnName, $columns) ? $columns[$columnName] : null;
                }

                foreach ($values as $name => &$value) {

                    if ($property->isRequired() && !is_int($value) && empty($value) && !$property->isFile()) {
                        $errors[$name] = Translation::of('Field is required', 'cms/form');

                    } elseif ($property['kind'] == 'PASSWORD') {

                        if (!(new Validator\NotEmpty())->isValid($value)) {

                            if (!$this->getSubject()) {
                                $errors[$name] = Translation::of("Field can't be empty", 'cms/form');
                            } else {
                                unset($values[$name]);
                            }

                        } else {
                            $value = sha1($value);
                        }

                    } elseif ($property->isFile()) {

                        if ($value == 'delete') {//delete file
                            if ($this->getSubject()) {
                                if (!empty(($fileName = $this->getSubject()->offsetGet($name)))) {
                                    if(file_exists($fileName)) unlink($fileName);
                                }
                            }

                            Registry::delete('http.request.files.subject.'.$property->getType('name').'.'.$name);

                        } elseif (($file = Registry::get('http.request.files.subject.'.$property->getType('name').'.'.$name)) && $file->getError()!=4){
                            /** @var UploadedFile $file */
                            if (($error = $file->getError())) {
                                $errors[$name] = sprintf(Translation::of('Gor error, php-code: %s, see http://php.net/manual/en/features.file-upload.errors.php', 'cms/form'), $error);
                            }

                            if ($errors || $hasErrors) continue;

                            $value = $this->uploadFile($property, $file);
                        } elseif ($property->isRequired() && !$this->getSubject()) {
                            $errors[$name] = Translation::of('Field is required', 'cms/form');
                        } else {
                            unset($values[$name]);
                        }

                    } elseif ($property['kind'] == 'DATE' && (new Validator\NotEmpty())->isValid($value)) {

                        if (!(new Validator\Regex('/^\d{4}\-\d{2}\-\d{2}$/'))->isValid($value)) {
                            $errors[$name] = Translation::of('Wrong date format', 'cms/form');
                        }

                    } elseif ($property['kind'] == 'DATETIME' && (new Validator\NotEmpty())->isValid($value)) {

                        if (!(new Validator\Regex('/^\d{4}\-\d{2}\-\d{2}\s+\d{2}:\d{2}:\d{2}$/'))->isValid($value)) {
                            $errors[$name] = Translation::of('Wrong datetime format', 'cms/form');
                        }

                    } elseif ($property['kind'] == 'NUMBER') {

                        if (empty($value)) {
                            $value = 0;
                        } else if (!(new Validator\Regex('/^\d+$/'))->isValid($value)) {
                            $errors[$name] = Translation::of('Wrong number format', 'cms/form');
                        }

                    } elseif ($property['kind'] == 'CHECKBOX') {

                        if (!(new Validator\NotEmpty())->isValid($value)) {
                            $value = 0;
                        } else {
                            $value = 1;
                        }

                    } else if(!(new Validator\NotEmpty())->isValid($value)){

                        // unset($values[$name]);
                    }
                }

                if ($values) $cleanSubject = array_merge($cleanSubject, $values);
            }
        });

        if ($errors || Registry::get('session.errors')) {
            if ($errors) {
                Registry::set('session.errors.'.$this->id, $errors);
            }

            Registry::set('session.old.'.$this->id, $columns);

            $hasErrors = true;
        }

        if ($relationForms) {
            foreach ($relationForms as $hash) {
                /** @var Form $form */
                if(($form = $hash['form'])->validate($hash['arguments'])) {
                    $cleanSubject[$hash['relationName']] = $form->getValidated();
                }
            }
        }

        if ($hasErrors || Registry::get('session.errors')) {
            return false;
        }

        $this->validated = [$this->getType('name') => $cleanSubject];

        return true;
    }

    protected function uploadFile ($property, $file) {
        /** @var UploadedFile $file */

        $folder = $property['kind'] == 'PHOTO' ? 'images' : 'files';
        $filePath = '/uploads/' . $folder . '/' . date('Y') . '/' . date('m') . '/' . date('d');
        $uploadPath = PUBLIC_PATH . $filePath;

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $key = sha1(uniqid('', true));
        $fileName = 'f_' . $key . substr($file->getClientFilename(), strrpos($file->getClientFilename(), '.'));
        $file->moveTo($uploadPath.'/'.$fileName);

        return $filePath.'/'.$fileName;
    }

    public function process()
    {
        if (!$this->validated) {
            throw new \Exception('Need validate first');
        }

        return $this->processSubject($this->validated);
    }

    protected function processSubject($formValidated)
    {
        //dump($formValidated);

        $relations = [];

        /** @var Type $type */
        if (!($typeName = key($formValidated)) || !($type = Registry::get('types')->get($typeName))) {
            return false;
        }

        $properties = $formValidated[$typeName];

        if(($typeRelations = $type->getDirectRelations()->filter(function (Attribute\Relation\DirectRelation $item) {
            return $item->isInternal();
        }))->count()) {
            foreach ($properties as $property => $values) {
                if ($typeRelations->has($property)) {
                    $relations [$property] = $values;
                    unset($properties[$property]);
                }
            }
        }

        /*dump($properties);*/

        /** @var Subject $subject */
        ($subject = Subjects::of($typeName)->model()->populate($properties, key_exists('id', $properties)))->save();

        if ($relations) {
            foreach ($relations as $relationName => $values) {
                /** @var Attribute\Relation\DirectRelation $relation */
                $relation = $typeRelations->get($relationName);
                $relationSubject = $this->processSubject($values);

                if ($relation->isFactory()) {
                    $subject->offsetSet($relationName, $relationSubject->getType('name'))->save();
                }

                if (!key_exists('id', current($values))) {
                    $subject->getRelations()->getRelationService($relationName)->save($relationSubject);
                }
            }
        }

        return $subject;
    }
}