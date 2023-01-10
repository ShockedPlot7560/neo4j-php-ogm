<?php

declare(strict_types=1);

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Hydrator;

use GraphAware\Neo4j\OGM\Metadata\GraphEntityMetadata;
use Laudis\Neo4j\Exception\PropertyDoesNotExistException;
use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;
use Laudis\Neo4j\Types\Node;
use Laudis\Neo4j\Types\Relationship;
use GraphAware\Neo4j\OGM\Common\Collection;
use GraphAware\Neo4j\OGM\Converters\Converter;
use GraphAware\Neo4j\OGM\EntityManager;
use GraphAware\Neo4j\OGM\Exception\MappingException;
use GraphAware\Neo4j\OGM\Metadata\NodeEntityMetadata;
use GraphAware\Neo4j\OGM\Metadata\RelationshipEntityMetadata;

class EntityHydrator
{
    private const INCOMING = 'INCOMING';

    private const OUTGOING = 'OUTGOING';

    private const BOTH = 'BOTH';

    private NodeEntityMetadata $classMetadata;

    public function __construct($className, private EntityManager $entityManager)
    {
        $this->classMetadata = $this->entityManager->getClassMetadataFor($className);
    }

    public function hydrateAll(CypherList $dbResult): array
    {
        $result = [];

        foreach ($dbResult->toArray() as $record) {
            $this->hydrateRecord($record, $result);
        }

        return $result;
    }

    public function hydrateSimpleRelationship(mixed $alias, CypherList $dbResult, object $sourceEntity)
    {
        if (0 === $dbResult->count()) {
            return;
        }

        $relationshipMetadata = $this->classMetadata->getRelationship($alias);
        $targetHydrator = $this->entityManager->getEntityHydrator($relationshipMetadata->getTargetEntity());
        $targetMeta = $this->entityManager->getClassMetadataFor($relationshipMetadata->getTargetEntity());
        $hydrated = $targetHydrator->hydrateAll($dbResult);

        $o = $hydrated[0];
        $relationshipMetadata->setValue($sourceEntity, $o);

        $mappedBy = $relationshipMetadata->getMappedByProperty();
        if ($mappedBy) {
            $targetRel = $targetMeta->getRelationship($mappedBy);
            if ($targetRel->isCollection()) {
                $targetRel->addToCollection($o, $sourceEntity);
            } else {
                $targetRel->setValue($o, $sourceEntity);
            }
        }
        $this->entityManager->getUnitOfWork()->addManagedRelationshipReference(
            $sourceEntity,
            $o,
            $relationshipMetadata->getPropertyName(),
            $relationshipMetadata
        );
    }

    public function hydrateSimpleRelationshipCollection($alias, CypherList $dbResult, $sourceEntity)
    {
        $relationshipMetadata = $this->classMetadata->getRelationship($alias);
        $this->initRelationshipCollection($alias, $sourceEntity);
        /** @var Collection $coll */
        $coll = $relationshipMetadata->getValue($sourceEntity);
        $targetHydrator = $this->entityManager->getEntityHydrator($relationshipMetadata->getTargetEntity());
        $targetMeta = $this->entityManager->getClassMetadataFor($relationshipMetadata->getTargetEntity());
        foreach ($dbResult->toArray() as $record) {
            $node = $record->get($targetMeta->getEntityAlias());
            $item = $targetHydrator->hydrateNode($node, $relationshipMetadata->getTargetEntity());
            $coll->add($item);
            $mappedBy = $relationshipMetadata->getMappedByProperty();
            if ($mappedBy) {
                $mappedRel = $targetMeta->getRelationship($mappedBy);
                if ($mappedRel->isCollection()) {
                    $mappedRel->initializeCollection($item);
                } else {
                    $mappedRel->setValue($item, $sourceEntity);
                }
                $this->entityManager->getUnitOfWork()->addManagedRelationshipReference(
                    $sourceEntity,
                    $item,
                    $relationshipMetadata->getPropertyName(),
                    $relationshipMetadata
                );
            }
        }
    }

    public function hydrateRelationshipEntity($alias, CypherList $dbResult, $sourceEntity)
    {
        $relationshipMetadata = $this->classMetadata->getRelationship($alias);
        /** @var RelationshipEntityMetadata $relationshipEntityMetadata */
        $relationshipEntityMetadata =
            $this->entityManager->getClassMetadataFor($relationshipMetadata->getRelationshipEntityClass());
        $otherClass = $this->guessOtherClassName($alias);
        $otherMetadata = $this->entityManager->getClassMetadataFor($otherClass);
        $otherHydrator = $this->entityManager->getEntityHydrator($otherClass);

        // initialize collection on source entity to avoid it being null
        if ($relationshipMetadata->isCollection()) {
            $relationshipMetadata->initializeCollection($sourceEntity);
        }

        if ($relationshipEntityMetadata->getStartNodeClass() == $relationshipEntityMetadata->getEndNodeClass()) {
            // handle relationships between nodes of the same type
            if ($relationshipMetadata->getDirection() === self::OUTGOING) {
                $startNodeIsSourceEntity = true;
            } elseif ($relationshipMetadata->getDirection() === self::INCOMING) {
                $startNodeIsSourceEntity = false;
            } else {
                throw new MappingException('Invalid Relationship Entity annotations. Direction BOTH not supported on'
                    . 'RelationshipEntities where startNode and endNode are of the same class');
            }
        } elseif ($relationshipEntityMetadata->getStartNodeClass() === $this->classMetadata->getClassName()) {
            $startNodeIsSourceEntity = true;
        } else {
            $startNodeIsSourceEntity = false;
        }

        // we iterate the result of records which are a map
        // {target: (Node) , re: (Relationship) }
        $k = $relationshipMetadata->getAlias();
        foreach ($dbResult->toArray() as $record) {
            /** @var Node $targetNode */
            $targetNode = $record->get($k)['target'];
            /** @var Relationship $relationship */
            $relationship = $record->get($k)['re'];

            // hydrate the target node :
            $targetEntity = $otherHydrator->hydrateNode($targetNode);

            // create the relationship entity
            $entity = $this->entityManager->getUnitOfWork()->createRelationshipEntity(
                $relationship,
                $relationshipEntityMetadata->getClassName(),
                $sourceEntity,
                $relationshipMetadata->getPropertyName()
            );

            // set properties on the relationship entity
            foreach ($relationshipEntityMetadata->getPropertiesMetadata() as $key => $propertyMetadata) {
                $fieldKey = $key;

                if ($propertyMetadata->getPropertyAnnotationMetadata()->hasCustomKey()) {
                    $fieldKey = $propertyMetadata->getPropertyAnnotationMetadata()->getKey();
                }

                if ($propertyMetadata->hasConverter()) {
                    $converter = Converter::getConverter($propertyMetadata->getConverterType(), $fieldKey);
                    $value = $converter->toPHPValue(
                        $relationship->getProperties()->toArray(),
                        $propertyMetadata->getConverterOptions()
                    );
                } else {
                    $properties = $relationship->getProperties();
                    $value = $properties->hasKey($fieldKey) ? $properties->get($fieldKey) : null;
                }
                $propertyMetadata->setValue($entity, $value);
            }

            // set the start and end node
            if ($startNodeIsSourceEntity) {
                $relationshipEntityMetadata->setStartNodeProperty($entity, $sourceEntity);
                $relationshipEntityMetadata->setEndNodeProperty($entity, $targetEntity);
            } else {
                $relationshipEntityMetadata->setStartNodeProperty($entity, $targetEntity);
                $relationshipEntityMetadata->setEndNodeProperty($entity, $sourceEntity);
            }

            // set the relationship entity on the source entity
            if (!$relationshipMetadata->isCollection()) {
                $relationshipMetadata->setValue($sourceEntity, $entity);
            } else {
                $relationshipMetadata->initializeCollection($sourceEntity);
                $relationshipMetadata->addToCollection($sourceEntity, $entity);
            }

            // detect the name of the property on the other node to populate reverse relation
            foreach ($otherMetadata->getRelationships() as $rel) {
                if (
                    $rel->isRelationshipEntity()
                    && $rel->getRelationshipEntityClass() === $relationshipEntityMetadata->getClassName()
                ) {
                    // if relation direction is not the opposite, do not populate
                    if (
                        ($direction = $rel->getDirection()) !== self::BOTH
                        && $direction === $relationshipMetadata->getDirection()
                    ) {
                        continue;
                    }
                    if (!$rel->isCollection()) {
                        $rel->setValue($targetEntity, $entity);
                    } else {
                        $rel->initializeCollection($targetEntity);
                        $rel->addToCollection($targetEntity, $entity);
                    }
                }
            }
        }
    }

    public function hydrateRecord(CypherMap $record, array &$result, $collection = false)
    {
        $cqlAliasMap = $this->getAliases();

        foreach ($record->keys() as $cqlAlias) {
            $data = $record->get($cqlAlias);
            $entityName = $cqlAliasMap[$cqlAlias];
            $data = $collection ? $data : [$data];
            foreach ($data as $node) {
                $id = $node->id();

                // Check the entity is not managed yet by the uow
                if (null !== $entity = $this->entityManager->getUnitOfWork()->getEntityById($id)) {
                    $result[] = $entity;
                    continue;
                }

                // create the entity
                $entity = $this->entityManager->getUnitOfWork()->createEntity($node, $entityName, $id);
                $this->hydrateProperties($entity, $node);
                $this->hydrateLabels($entity, $node);
                $this->entityManager->getUnitOfWork()->addManaged($entity);

                $result[] = $entity;
            }
        }
    }

    public function hydrateNode(?Node $node, $class = null)
    {
        if ($node === null) {
            return null;
        }

        $cm = null === $class ? $this->classMetadata->getClassName() : $class;
        $id = $node->id();

        // Check the entity is not managed yet by the uow
        if (null !== $entity = $this->entityManager->getUnitOfWork()->getEntityById($id)) {
            return $entity;
        }

        // create the entity
        $entity = $this->entityManager->getUnitOfWork()->createEntity($node, $cm, $id);
        $this->hydrateProperties($entity, $node);
        $this->hydrateLabels($entity, $node);

        return $entity;
    }

    public function refresh(Node $node, object $entity)
    {
        $this->hydrateProperties($entity, $node);
        $this->hydrateLabels($entity, $node);
    }

    protected function hydrateProperties($object, Node $node)
    {
        foreach ($this->classMetadata->getPropertiesMetadata() as $key => $metadata) {
            $fieldKey = $key;

            if ($metadata->getPropertyAnnotationMetadata()->hasCustomKey()) {
                $fieldKey = $metadata->getPropertyAnnotationMetadata()->getKey();
            }

            if ($metadata->hasConverter()) {
                $converter = Converter::getConverter($metadata->getConverterType(), $fieldKey);
                $value = $converter->toPHPValue($node->properties()->toArray(), $metadata->getConverterOptions());
            } else {
                try {
                    $value = $node->getProperty($fieldKey);
                } catch (PropertyDoesNotExistException) {
                    $value = null;
                }
            }
            $metadata->setValue($object, $value instanceof CypherList ? $value->toArray() : $value);
        }
    }

    protected function hydrateLabels($object, Node $node)
    {
        foreach ($this->classMetadata->getLabeledProperties() as $labeledProperty) {
            if (in_array($labeledProperty->getLabelName(), $node->labels()->toArray())) {
                $labeledProperty->setLabel($object, true);
            } else {
                $labeledProperty->setLabel($object, false);
            }
        }
    }

    protected function getAliases(): array
    {
        return [$this->classMetadata->getEntityAlias() => $this->classMetadata->getClassName()];
    }

    private function guessOtherClassName($alias): string
    {
        $relationshipMetadata = $this->classMetadata->getRelationship($alias);
        /** @var RelationshipEntityMetadata $relationshipEntityMetadata */
        $relationshipEntityMetadata =
            $this->entityManager->getClassMetadataFor($relationshipMetadata->getRelationshipEntityClass());
        /* @todo will not work for Direction.BOTH */
        return $relationshipEntityMetadata->getOtherClassNameForOwningClass($this->classMetadata->getClassName());
    }

    private function initRelationshipCollection($alias, $sourceEntity)
    {
        $this->classMetadata->getRelationship($alias)->initializeCollection($sourceEntity);
    }
}
