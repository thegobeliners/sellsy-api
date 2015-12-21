<?php

namespace Sellsy\Models\Catalogue;

/**
 * Class Item
 * @package Sellsy\Models\Catalogue
 */
class Item
{
    /**
     * @var int
     * @copy
     */
    public $id;

    /**
     * @var boolean
     * @copy actif
     * @convert boolean
     */
    public $isActive;

    /**
     * @var boolean
     * @copy
     * @convert boolean
     */
    public $isEnabled;

    /**
     * @var string
     * @copy
     */
    public $name;

    /**
     * @var string
     * @copy
     */
    public $tradename;
} 