<?php

namespace UDeploy\Taxi\Traits;

trait HasAttributes
{
    public function get($property): mixed
    {
        if ($this->has($property)) {
            return $this->attributes[$property];
        }

        return null;
    }

    public function has($property): bool
    {
        return array_key_exists($property, $this->attributes);
    }

    public function __set($property, $value)
    {
        return $this->attributes[$property] = $value;
    }

    public function __get($property): mixed
    {
        return $this->has($property)
            ? $this->attributes[$property]
            : null;
    }
}
