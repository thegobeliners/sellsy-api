<?php

namespace Sellsy\Clients;

use Sellsy\Criteria\Documents\SearchCriteria;
use Sellsy\Collections\Documents\DocumentCollection;
use Sellsy\Criteria\Order;
use Sellsy\Criteria\Paginator;
use Sellsy\Interfaces\AdapterInterface;

/**
 * Class Documents
 * @package Sellsy\Clients
 */
class Documents
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @param SearchCriteria $criteria
     * @param Order|null $order
     * @param Paginator|null $paginator
     * @return DocumentCollection
     */
    public function searchDocuments(SearchCriteria $criteria, Order $order = null, Paginator $paginator = null)
    {
        return $this->adapter->map(new DocumentCollection())->call('Document.getList', $criteria, $order, $paginator);
    }
} 