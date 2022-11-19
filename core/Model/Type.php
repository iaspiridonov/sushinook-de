<?php namespace Core\Model;

use Core\Facade\App;
use Core\Collection;
use Core\Service\Registry;
use Core\Service\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Ddl\Column\BigInteger;
use Zend\Db\Sql\Ddl\Column\Boolean;
use Zend\Db\Sql\Ddl\Constraint\PrimaryKey;
use Zend\Db\Sql\Ddl\Index\Index;
use Zend\Db\Sql\Sql;
use Core\Gateway;

class Type extends AbstractModel {

    protected $table = Gateway\Types::TABLE;

    protected $attributes;

    public function __construct()
    {
        $this->sql = new Sql(App::getInstance()->getContainer()->get(Adapter::class), $this->table);

        $this->attributes = (new Collection\Type\AttributesCollection)->setType($this);
    }

    public function subjectsTable($field = null)
    {
        return Gateway\Types::subjectsTable($this['name'], $field);
    }

    public function relationsTable($field = null)
    {
        return Gateway\Types::relationsTable($this['name'], $field);
    }

    public function setAttributes($items)
    {
        $this->attributes->populate($items);

        return $this;
    }

    /** @return Collection\Type\AttributesCollection */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /** @return Collection\Type\AttributesCollection */
    public function getRelations()
    {
        return $this->getAttributes()->filter(function (Type\Attribute $item) {
            return $item->isRelation();
        });
    }

    /** @return Collection\Type\AttributesCollection */
    public function getDirectRelations()
    {
        return $this->getRelations()->filter(function (Type\Attribute\Relation $item) {
            return $item->isDirectRelation();
        });
    }

    /** @return Collection\Type\AttributesCollection */
    public function getBackRelations()
    {
        return $this->getRelations()->filter(function (Type\Attribute\Relation $item) {
            return $item->isBackRelation();
        });
    }

    /** @return Collection\Type\AttributesCollection */
    public function getProperties()
    {
        return $this->getAttributes()->filter(function (Type\Attribute $item) {
            return $item->isProperty();
        });
    }

    /** @return Collection\Type\AttributesCollection */
    public function getCommonProperties()
    {
        return $this->getProperties()->filter(function (Type\Attribute\Property $item) {
            return !$item->isTranslatable();
        });
    }

    /** @return Collection\Type\AttributesCollection */
    public function getTranslatableProperties()
    {
        return $this->getProperties()->filter(function (Type\Attribute\Property $item) {
            return $item->isTranslatable();
        });
    }

    public function isSortable()
    {
        return $this->getProperties()->has('sort');
    }

    public function delete()
    {
        return (new Gateway\Types)->delete(['id' => $this->id]);
    }

    public function save()
    {
        if ($this->rowExistsInDatabase()) {
            //UPDATE
            $oldType = (new Gateway\Types)->find($this->id);

            #TYPE CHANGE NAME, tables etc
            if ($this->name != $oldType->name) {

                Registry::get('types')->put($this->name, $this);

                $sql = new Sql(App::getInstance()->getContainer()->get(Adapter::class));

                $sql->getAdapter()->query(
                    'ALTER TABLE `' . $oldType->subjectsTable() . '` RENAME `' . $this->subjectsTable() . '`',
                    Adapter::QUERY_MODE_EXECUTE
                );

                if ($oldType->getDirectRelations()->count()) {
                    $sql->getAdapter()->query(
                        'ALTER TABLE `' . $oldType->relationsTable() . '` RENAME `' . $this->relationsTable() . '`',
                        Adapter::QUERY_MODE_EXECUTE
                    );
                }

                $attributes = (new Gateway\TypesAttributes)->get();

                if ($attributes->count()) {

                    $attributes->each(function ($item) use ($oldType) {
                        /** @var Type\Attribute $item */
                        $needSave = false;

                        if ($item->type == $oldType->name) {
                            $item->offsetSet('type', $this->name);

                            $needSave = true;
                        }

                        if ($item->isRelation()) {
                            /** @var Type\Attribute\Relation $item */

                            if ($item->isDirectRelation()) {
                                /** @var Type\Attribute\Relation\DirectRelation $item */

                                if ($item->isFactory()) {

                                    if (($key = array_search($oldType->name, $item['settings']['is'])) !== false) {

                                        $settings = $item['settings'];
                                        $settings['is'][$key] = $this->name;
                                        $item['settings'] = $settings;

                                        Gateway\Subjects::of($item->type)->update([$item->name => $this->name], [$item->name => $oldType->name]);

                                        $needSave = true;
                                    }

                                } elseif ($item->is($oldType->name)) {
                                    $settings = $item['settings'];
                                    $settings['is'] = $this->name;
                                    $item['settings'] = $settings;

                                    $needSave = true;
                                }
                            }
                        }

                        if ($needSave) {
                            $item->save();
                        }

                    });
                }
            }
        } else {
            // CREATE

            Table::create($this->subjectsTable())
                ->addColumn(new BigInteger('id', false, NULL, ['autoincrement' => true]))
                ->addColumn(new Boolean('is_related', false, 0))
                ->addConstraint(new PrimaryKey('id'))
                ->addConstraint(new Index('is_related'))
                ->execute();
        }

        return parent::save();
    }
}