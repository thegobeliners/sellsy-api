<?php

namespace Sellsy\Models\Documents;

/**
 * Class Proforma
 * @package Sellsy\Models\Documents
 */
class Proforma extends Document implements ProformaInterface
{
    /**
     * @var \DateTime
     * @copy expireDate
     */
    public $expireAt;
}