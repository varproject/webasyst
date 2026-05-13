<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2024 waResearchLab
 */

abstract class devapiEntity implements JsonSerializable
{
    public function __construct($data)
    {
        foreach ($this->getProperties() as $property) {
            $name = $property->name;
            if (isset($data[$name])) $this->$name = $data[$name];
        }
    }

    public function jsonSerialize(): array
    {
        $data = [];
        foreach ($this->getProperties() as $property) {
            $name = $property->name;
            $data[$name] = $this->$name;
        }
        return $data;
    }

    protected function getProperties($filter = ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED) {
        $ref = new ReflectionClass($this);
        return $ref->getProperties($filter);
    }
}