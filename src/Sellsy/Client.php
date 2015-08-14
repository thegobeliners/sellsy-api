<?php

namespace Sellsy;

use Sellsy\Adapters\BaseAdapter;
use Sellsy\Clients\Catalogue;
use Sellsy\Models\ApiInfos;

/**
 * Class Client
 * @package Sellsy
 */
class Client
{
    /**
     * @var BaseAdapter
     */
    protected $adapter;

    /**
     * @var array
     */
    protected $clients = array();

    /**
     * @param BaseAdapter $adapter
     */
    public function __construct($adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @return mixed
     */
    public function getApiInfos()
    {
        return $this->adapter->map(new ApiInfos())->call(array(
            'method' => 'Infos.getInfos',
            'params' => array(),
        ));
    }

    /**
     * @return Catalogue
     */
    public function catalogue()
    {
        if (! isset($clients['catalogue'])) {
            $clients['catalogue'] = new Catalogue($this->adapter);
        }

        return $clients['catalogue'];
    }
}