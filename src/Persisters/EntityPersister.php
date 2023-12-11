<?php

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

/*
 * This file is part of the GraphAware Neo4j PHP OGM package.
 *
 * (c) GraphAware Ltd <info@graphaware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GraphAware\Neo4j\OGM\Persisters;

use GraphAware\Neo4j\OGM\Converters\Converter;
use Laudis\Neo4j\Databags\Statement;
use function is_bool;
use function is_null;
use function is_string;
use function sprintf;

class EntityPersister extends BasicEntityPersister
{
	public function getCreateQuery(object $object) : Statement
	{
		[$propertyValues, $extraLabels, $removeLabels] = $this->getBaseQueryProperties($object);

		$paramsQuery = "";
		if (!empty($propertyValues)) {
			foreach ($propertyValues as $key => $value) {
				$formattedValue = is_string($value) ? "'$value'" : $value;
				$formattedValue = is_bool($value) ? ($value ? 'true' : 'false') : $formattedValue;
				$formattedValue = is_null($value) ? 'null' : $formattedValue;
				$paramsQuery .= $key . ': ' . $formattedValue;
			}
		}
		$query = sprintf('CREATE (n:%s', $this->classMetadata->getLabel());
		if(!empty($paramsQuery)) {
			$query .= sprintf(' {%s})', $paramsQuery);
		}
		if (!empty($extraLabels)) {
			foreach ($extraLabels as $label) {
				$query .= ' SET n:' . $label;
			}
		}
		if (!empty($removeLabels)) {
			foreach ($removeLabels as $label) {
				$query .= ' REMOVE n:' . $label;
			}
		}

		$query .= ' RETURN id(n) as id';

		return Statement::create($query);
	}

	public function getUpdateQuery(object $object) : Statement
	{
		[$propertyValues, $extraLabels, $removeLabels] = $this->getBaseQueryProperties($object);

		$id = $this->classMetadata->getIdValue($object);
		$query = sprintf("MATCH (n) WHERE id(n) = {$this->paramStyle} ", 'id');
		if (!empty($propertyValues)) {
			foreach ($propertyValues as $key => $value) {
				$formattedValue = is_string($value) ? "'$value'" : $value;
				$formattedValue = is_bool($value) ? ($value ? 'true' : 'false') : $formattedValue;
				$formattedValue = is_null($value) ? 'null' : $formattedValue;
				$query .= ' SET n.' . $key . ' = ' . $formattedValue;
			}
		}
		if (!empty($extraLabels)) {
			foreach ($extraLabels as $label) {
				$query .= ' SET n:' . $label;
			}
		}
		if (!empty($removeLabels)) {
			foreach ($removeLabels as $label) {
				$query .= ' REMOVE n:' . $label;
			}
		}

		return Statement::create($query, ['id' => $id]);
	}

	public function refresh(int $id, object $entity) : void
	{
		$label = $this->classMetadata->getLabel();
		$query = sprintf("MATCH (n:%s) WHERE id(n) = {$this->paramStyle} RETURN n", $label, 'id');
		$result = $this->entityManager->getDatabaseDriver()->run($query, ['id' => $id]);

		if ($result->count() > 0) {
			$node = $result->first()->get('n');
			$this->entityManager->getEntityHydrator($this->className)->refresh($node, $entity);
		}
	}

	public function getDetachDeleteQuery(object $object) : Statement
	{
		$query = sprintf("MATCH (n) WHERE id(n) = {$this->paramStyle} DETACH DELETE n", 'id');
		$id = $this->classMetadata->getIdValue($object);

		return Statement::create($query, ['id' => $id]);
	}

	public function getDeleteQuery(object $object) : Statement
	{
		$query = sprintf("MATCH (n) WHERE id(n) = {$this->paramStyle} DELETE n", 'id');
		$id = $this->classMetadata->getIdValue($object);

		return Statement::create($query, ['id' => $id]);
	}

	private function getBaseQueryProperties(object $object) : array
	{
		$propertyValues = [];
		$extraLabels = [];
		$removeLabels = [];
		foreach ($this->classMetadata->getPropertiesMetadata() as $field => $meta) {
			$fieldId = $this->classMetadata->getClassName() . $field;
			$fieldKey = $field;

			if ($meta->getPropertyAnnotationMetadata()->hasCustomKey()) {
				$fieldKey = $meta->getPropertyAnnotationMetadata()->getKey();
			}

			if ($meta->hasConverter()) {
				$converter = Converter::getConverter($meta->getConverterType(), $fieldId);
				$propertyValues[$fieldKey] = $converter->toDatabaseValue(
					$meta->getValue($object),
					$meta->getConverterOptions()
				);
			} else {
				$propertyValues[$fieldKey] = $meta->getValue($object);
			}
		}

		foreach ($this->classMetadata->getLabeledProperties() as $labeledProperty) {
			if ($labeledProperty->isLabelSet($object)) {
				$extraLabels[] = $labeledProperty->getLabelName();
			} else {
				$removeLabels[] = $labeledProperty->getLabelName();
			}
		}

		return [$propertyValues, $extraLabels, $removeLabels];
	}
}
