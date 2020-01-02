<?php

namespace AmeliaBooking\Application\Commands;

/**
 * Class Command
 *
 * @package AmeliaBooking\Application\Commands
 */
abstract class Command
{

    protected $args;
    protected $container;
    private $fields = [];

    /**
     * Command constructor.
     *
     * @param $args
     */
    public function __construct($args)
    {
        $this->args = $args;
        if (isset($args['type'])) {
            $this->setField('type', $args['type']);
        }
    }

    /**
     * @return mixed
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param mixed $arg Argument to be fetched
     *
     * @return null
     */
    public function getArg($arg)
    {
        return isset($this->args[$arg]) ? $this->args[$arg] : null;
    }

    /**
     * @param $fieldName
     * @param $fieldValue
     */
    public function setField($fieldName, $fieldValue)
    {
        $this->fields[$fieldName] = $fieldValue;
    }

    /**
     * @param $fieldName
     */
    public function removeField($fieldName)
    {
        unset($this->fields[$fieldName]);
    }

    /**
     * Return a single field
     *
     * @param $fieldName
     *
     * @return mixed|null
     */
    public function getField($fieldName)
    {
        return isset($this->fields[$fieldName]) ? $this->fields[$fieldName] : null;
    }

    /**
     * Return all fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }
}
