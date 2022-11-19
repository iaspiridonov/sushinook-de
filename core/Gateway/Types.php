<?php namespace Core\Gateway;

use Core\Model\Type;
use Core\Collection;
use Core\Model\Type\Attribute;
use Core\Service\Table;
use Illuminate\Support\Str;
use Zend\Db\Sql\Delete;

class Types extends AbstractGateway
{
    const TABLE = 'types';

    public static function subjectsTable($typeName, $field = null)
    {
        return 'type_'.Str::lower($typeName).'_subjects'.($field ? '.'.$field : '');
    }

    public static function relationsTable($typeName, $field = null)
    {
        return 'type_'.Str::lower($typeName).'_relations'.($field ? '.'.$field : '');
    }

    public function model()
    {
        return new Type;
    }

    public function collection($array)
    {
        return new Collection\TypesCollection($array);
    }

    protected function setTable()
    {
        $this->table = static::TABLE;
    }

    public function get()
    {
        return $this->loadAttributes(parent::toCollection()->keyBy('name'));
    }

    protected function loadAttributes(Collection\TypesCollection $types)
    {
        if (!$types->count()) return $types;

        $attributes = (new TypesAttributes)->select(['type' => $types->pluck('name')->toArray()])->get()->sortBy(function ($item) {
            return $item['settings']['interface']['sort'];
        });

        if($attributes->count()) {
            $attributes->each(function(Attribute $item) use ($types) {
                $types->get($item['type'])->getAttributes()->put($item['name'], $item);
            });
        }

        return $types;
    }

    protected function executeDelete(Delete $delete)
    {
        $types = $this->select($delete->where)->get();

        if ($types->count()) {
            $types->each(function (Type $type) {
                Subjects::of($type->name)->delete(true);//delete subjects and entire all relations

                Table::drop($type->subjectsTable());

                if ($type->getDirectRelations()->count()) {
                    Table::drop($type->relationsTable());
                }

                (new TypesAttributes)->delete(['type = ?' => $type->name]);

                if (($attributes = (new TypesAttributes)->get())) {

                    $attributes->each(function ($attribute) use ($type){
                        /** @var Attribute\Relation\DirectRelation $attribute */
                        if ($attribute->isRelation() && $attribute->is($type->name)) {

                            if ($attribute->isDirectRelation() && $attribute->isFactory()) {

                                $settings =  $attribute->settings;
                                $key = array_search($type->name, $settings['is']);
                                unset($settings['is'][$key]);

                                if (count($settings['is']) < 2) {

                                    $settings['is'] = current($settings['is']);

                                    Table::alter($attribute->getType()->subjectsTable())->dropColumn($attribute->name)->execute();

                                }

                                $attribute->offsetSet('settings', $settings);
                                $attribute->save();

                            } else {
                                $attribute->delete();
                            }
                        }
                    });
                }
            });
        }

        return parent::executeDelete($delete);
    }
}