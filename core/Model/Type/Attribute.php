<?php namespace Core\Model\Type;

use Core\Facade\App;
use Core\Service\Locale;
use Core\Service\Registry;
use Core\Gateway\Relations;
use Core\Gateway\TypesAttributes;
use Core\Service\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Core\Model;
use Core\Gateway;
use Zend\Db\Sql\Where;

class Attribute extends Model\AbstractModel {

    protected $table = TypesAttributes::TABLE;

    public function __construct()
    {
        $this->sql = new Sql(App::getInstance()->getContainer()->get(Adapter::class), $this->table);
    }

    public function getType($attributeName = null)
    {
        /** @var Model\Type $type */
        if (!($type = Registry::get('types')->get($this->offsetGet('type')))) {
            throw new \Exception('Attribute "'.$this->offsetGet('name').'" can\'t find own type "'.$this->offsetGet('type').'" in registry');
        }

        return $attributeName ? $type->offsetGet($attributeName) : $type;
    }

    public function isProperty()
    {
        return false;
    }

    public function isRelation()
    {
        return false;
    }

    public function getColumnName()
    {
        return $this->name;
    }

    public function getColumn()
    {
        return TypesAttributes::getColumn($this->kind, $this->getColumnName());
    }

    public function populate(array $rowData, $rowExistsInDatabase = false)
    {
        if (!is_array($rowData['settings'])) {
            $rowData['settings'] = !empty($rowData['settings']) && is_string($rowData['settings']) ? json_decode($rowData['settings'], true) : [];
        }

        return parent::populate($rowData, $rowExistsInDatabase);
    }

    public function save()
    {
        if ($this->rowExistsInDatabase()) {
            // UPDATE ATTRIBUTE IS THE MOST POWERFULL SHIT

            /** @var Attribute $oldAttribute */
            /** @var Attribute $editedAttribute */
            $oldAttribute = (new TypesAttributes)->find($this->id);
            $editedAttribute = (new Attribute\Factory)->exchangeArray($this->data);

            if ($oldAttribute->isProperty()) {/** @var Attribute\Property $oldAttribute */

                if ($editedAttribute->isProperty()) {/** @var Attribute\Property $editedAttribute */

                    if ($editedAttribute->isTranslatable() != $oldAttribute->isTranslatable()) {

                        if ($editedAttribute->isTranslatable()) {//become translatable
                            /** @var Attribute\Property $oldAttribute */
                            /** @var Attribute\Property\TranslatableProperty $editedAttribute */
                            $table = Table::alter($this->getType()->subjectsTable())->changeColumn($oldAttribute->getColumnName(), $editedAttribute->getColumn(Locale::getDefaultLocale()));

                            foreach (Locale::getLocales() as $locale) {

                                if ($locale == Locale::getDefaultLocale()) continue;

                                $table->addColumn($editedAttribute->getColumn($locale));
                            }

                            $table->execute();

                        } else {//become not translatable
                            /** @var Attribute\Property\TranslatableProperty $oldAttribute */
                            /** @var Attribute\Property $editedAttribute */

                            $table = Table::alter($this->getType()->subjectsTable())->changeColumn($oldAttribute->getColumnName(Locale::getDefaultLocale()), $editedAttribute->getColumn());

                            foreach (Locale::getLocales() as $locale) {

                                if ($locale == Locale::getDefaultLocale()) continue;

                                $table->dropColumn($oldAttribute->getColumnName($locale));
                            }

                            $table->execute();
                        }

                    } elseif ($oldAttribute['name'] != $editedAttribute['name'] || $oldAttribute['kind'] != $editedAttribute['kind']) {

                        if ($oldAttribute->isTranslatable()) {
                            /** @var Attribute\Property\TranslatableProperty $oldAttribute */
                            /** @var Attribute\Property\TranslatableProperty $editedAttribute */
                            $table = Table::alter($this->getType()->subjectsTable());

                            foreach (Locale::getLocales() as $locale) {
                                $table->changeColumn($oldAttribute->getColumnName($locale), $editedAttribute->getColumn($locale));
                            }

                            $table->execute();
                        } else {
                            Table::alter($this->getType()->subjectsTable())->changeColumn($oldAttribute->getColumnName(), $editedAttribute->getColumn())->execute();
                        }
                    }

                } else { /** @var Attribute\Relation $editedAttribute */

                    $oldAttribute->delete(false);

                    if ($editedAttribute->isDirectRelation()) {/** @var Attribute\Relation\DirectRelation $editedAttribute */

                        if($editedAttribute->isFactory()) {
                            Table::alter($this->getType()->subjectsTable())->addColumn($editedAttribute->getColumn())->execute();
                        }
                    }
                }

            } elseif ($oldAttribute->isRelation()) { /** @var Attribute\Relation $oldAttribute */

                if ($editedAttribute->isProperty()) { /** @var Attribute\Property $editedAttribute */

                    if ($oldAttribute->isDirectRelation()) {/** @var Attribute\Relation\DirectRelation $oldAttribute */
                        $oldAttribute->delete(false);
                    }

                    if ($editedAttribute->isTranslatable()) {
                        /** @var Attribute\Property\TranslatableProperty $editedAttribute */
                        $table = Table::alter($this->getType()->subjectsTable());

                        foreach (Locale::getLocales() as $locale) {
                            $table->addColumn($editedAttribute->getColumn($locale));
                        }

                        $table->execute();
                    } else {
                        Table::alter($this->getType()->subjectsTable())->addColumn($editedAttribute->getColumn())->execute();
                    }

                } elseif ($editedAttribute->isRelation()) { /** @var Attribute\Relation $editedAttribute */

                    if ($editedAttribute->isDirectRelation()) {
                        /** @var Attribute\Relation\DirectRelation $editedAttribute */
                        if ($oldAttribute->isDirectRelation()) {
                            /** @var Attribute\Relation\DirectRelation $oldAttribute */
                            if ($editedAttribute->isFactory() && !$oldAttribute->isFactory()) {

                                Table::alter($this->getType()->subjectsTable())->addColumn($editedAttribute->getColumn())->execute();

                                Gateway\Subjects::of($this->getType()['name'])->update([
                                    $editedAttribute['name'] => $oldAttribute['settings']['is']
                                ]);

                            } elseif (!$editedAttribute->isFactory() && $oldAttribute->isFactory()) {

                                /*
                                Если были найдены объекты, в которых поле ['settings']['is'] раньше было
                                массивом, а теперь там указан лишь один тип, то мы должны пробежаться по этим объектам
                                и поудалять связи на классы, которых больше нет в ['settings']['is']
                                */
                                $subjects = Gateway\Subjects::of($this->getType()['name'])->select([$oldAttribute['name'].' <> ?' => $editedAttribute['settings']['is']])->get();

                                if ($subjects->count()) {

                                    Gateway\Relations::of($this->getType()['name'])->delete([
                                        'relation' => $oldAttribute['name'],
                                        'subject_id' => $subjects->pluck('id')->toArray()
                                    ]);
                                }

                                Table::alter($this->getType()->subjectsTable())->dropColumn($oldAttribute['name'])->execute();

                            } elseif ($editedAttribute['settings']['is'] != $oldAttribute['settings']['is']) {

                                if ($editedAttribute->isFactory()) {//both are arrays, not match items
                                    $subjects = Gateway\Subjects::of($this->getType('name'))->select(function (Select $select) use ($oldAttribute, $editedAttribute){
                                        $select->where->notIn($oldAttribute['name'], $editedAttribute['settings']['is']);
                                    })->get();

                                    if ($subjects->count()) {

                                        $subjectsId = $subjects->pluck('id')->toArray();

                                        Gateway\Relations::of($this->getType('name'))->delete([
                                            'relation' => $oldAttribute['name'],
                                            'subject_id' => $subjectsId
                                        ]);

                                        Gateway\Subjects::of($this->getType('name'))->update([
                                            $oldAttribute['name'] => ''
                                        ], ['id' => $subjectsId]);
                                    }
                                    //TODO correct delete subjects of types wich were deleted from relation

                                } elseif (($editedType = Registry::get('types')->get($editedAttribute['settings']['is']))->id != ($oldType = Registry::get('types')->get($oldAttribute['settings']['is']))->id) { //both are strings, not match, if this is not renamed same type, then this is new type, then delete old subject relations

                                    /** @var Model\Type $oldType */

                                    if ($editedAttribute->isStrong()) {
                                        Gateway\Subjects::of($oldAttribute['settings']['is'])->delete(function (Where $where) use ($oldType, $oldAttribute) {
                                            $where->in('id', (new Select($oldType->relationsTable()))->columns(['relation_subject_id'])->where(['relation = ?' => $oldAttribute['name']]));
                                        });
                                    } else {
                                        Gateway\Relations::of($this->getType('name'))->delete([
                                            'relation' => $oldAttribute['name']
                                        ]);
                                    }
                                }

                            }

                            if ($editedAttribute['name'] != $oldAttribute['name']) {//если поменялось имя

                                if ($editedAttribute->isFactory() && $oldAttribute->isFactory()) {
                                    Table::alter($this->getType()->subjectsTable())->changeColumn($oldAttribute['name'], $editedAttribute->getColumn())->execute();
                                }

                                Gateway\Relations::of($this->getType()['name'])->update([
                                    'relation' => $editedAttribute['name']
                                ], [
                                    'relation' => $oldAttribute['name']
                                ]);
                            }

                        } elseif ($oldAttribute->isBackRelation()) { //was back relation, become direct

                            if ($editedAttribute->isFactory()) {
                                Table::alter($this->getType()->subjectsTable())->addColumn($editedAttribute->getColumn())->execute();
                            }
                        }

                    } elseif ($editedAttribute->isBackRelation() && $oldAttribute->isDirectRelation()) {//become back relation, was direct relation. need to delete all relations
                        $oldAttribute->delete(false);
                    }
                }
            }
        }

        $this->offsetSet('settings', json_encode($this->offsetGet('settings')));

        $hasRelationsTable = $this->getType()->getDirectRelations()->count();

        if ($affectedRows = parent::save()) {

            if (($mustHaveRelationsTable = (new TypesAttributes())->select(['type' => $this->getType('name'), 'kind' => Model\Type\Attribute\Relation\DirectRelation::KIND])->count())) {

                if (!$hasRelationsTable) {
                    Relations::of($this->getType('name'))->createTable();
                }

            } else {
                if ($hasRelationsTable) {
                    Table::drop($this->getType()->relationsTable());
                }
            }
        }

        return $affectedRows;
    }
}