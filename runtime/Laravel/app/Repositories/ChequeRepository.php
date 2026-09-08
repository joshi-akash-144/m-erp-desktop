<?php

namespace App\Repositories;

use App\Models\ChequeMaster;

class ChequeRepository extends BaseRepository
{

    public function __construct(ChequeMaster $chequeMaster)
    {
        parent::__construct($chequeMaster);
    }
}
