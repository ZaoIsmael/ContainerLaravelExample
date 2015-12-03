<?php

use Closure;
use InvalidArgumentException;
use ReflectionClass;

class Container
{
    protected $shared = [];
    protected $bindings = [];

    public function bind($name, $resolver)
    {
        $this->bindings[$name] = [
            'resolver' => $resolver
        ];
    }

    public function instance($name, $object)
    {
        $this->shared[$name] = $object;
    }

    public function make($name)
    {
        if (isset ($this->shared[$name])) {
            return $this->shared[$name];
        }

        $resolver = $this->bindings[$name]['resolver'];

        if ($resolver instanceof Closure) {
            return $resolver($this);
        }

        return $this->build($resolver);
    }

    protected function build($class)
    {
        $reflection = new ReflectionClass($class);

        if(! $reflection->isInstantiable()) {
            throw new InvalidArgumentException("$class is not instantiable");
        }

        if(is_null($constructor = $reflection->getConstructor())) {
            return new $class;
        }

        $dependency = $this->instanceArgs($constructor->getParameters()); 

        return $reflection->newInstanceArgs($dependency);
    }

    protected function instanceArgs($parameters) 
    {
        return $dependency = array_map(function ($parameter) {
            return $this->build($parameter->getClass()->getName());
        }, $parameters);
    }
}
