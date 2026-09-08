<?php

return [

    'company' => [
        3112 => 'Shree Mahakali Corporation',
        3113 => 'Shree Mahakali Agro Feed Pvt Ltd',
        3114 => 'Ambika Trading Company',
        3115 => 'Shree Vinayak Corporation',
        3116 => 'Shree Mahakali Feed Trade LLP',
        3117 => 'Shree Ram Corporation',
        3118 => 'Shree Laxmi Agro Industry',
        3119 => 'Shree Bhavana Corporation',
        // 3119 => 'SMC IMPEX',
    ],

    'prefix' => [
        3112 => 'MC-',
        3113 => 'MAF-',
        3114 => 'ATC-',
        3115 => 'SVC-',
        3116 => 'SMFT-',
        3117 => 'SRC-',
        3118 => 'SLAI-',
        3119 => 'SBC-',
        // 19 => 'SMCTR-',
    ],

    /**
     * Maps Bill To account_id (in Shree Ambica Roadlines / company 9)
     * to the corresponding client company_id in the GodownModule.
     * Used when fetching GRN / LR Number data from GodownModule.
     */
    'account_company' => [
        3112 => 1,  // Shree Mahakali Corporation
        3113 => 2,  // Shree Mahakali Agro Feed Pvt. Ltd.
        3114 => 3,  // Ambica Trading Company
        3115 => 4,  // Shree Vinayak Corporation
        3116 => 5,  // Shree Mahakali Feed Trade LLP
        3117 => 6,  // Shree Ram Corporation
        3118 => 7,  // Shree Laxmi Agro Industry
        3119 => 8,  // Shree Bhavana Corporation
    ],

];
