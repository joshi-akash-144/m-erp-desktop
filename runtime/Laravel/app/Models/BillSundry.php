<?php

namespace App\Models;

use App\Traits\HasCompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillSundry extends BaseMaster
{
    use HasCompanyContext;
    public const ADDICTIVE = 'additive';
    public const SUBTRACTIVE = 'subtractive';
    public const BILL_SUNDRY_TYPE = ['additive', 'subtractive'];
    // public const BILL_SUNDRY_AMOUNT_ROUND_OFF = [true, false];
    // public const PURCHASE_ADJUST_IN_AMOUNT = [true, false];
    // public const PURCHASE_ADJUST_IN_PARTY_AMOUNT = [true, false];
    // public const SALE_ADJUST_IN_AMOUNT = [true, false];
    // public const SALE_ADJUST_IN_PARTY_AMOUNT = [true, false];
     // Common account types
    public const SPECIFY_ACCOUNT             = 'specify_account';
    public const SPECIFY_ACCOUNT_IN_VOUCHER  = 'specify_account_in_voucher';

    // Master array for valid account types
    public const ACCOUNT_TYPES = [
        self::SPECIFY_ACCOUNT,
        self::SPECIFY_ACCOUNT_IN_VOUCHER,
    ];

    // Assign same valid list to each specific group
    public const PURCHASE_ACCOUNT_TYPE        = self::ACCOUNT_TYPES;
    public const PURCHASE_PARTY_ACCOUNT_TYPE  = self::ACCOUNT_TYPES;
    public const SALE_ACCOUNT_TYPE            = self::ACCOUNT_TYPES;
    public const SALE_PARTY_ACCOUNT_TYPE      = self::ACCOUNT_TYPES;
    // public const PURCHASE_POST_OVER_AND_ABOVE = [true, false];
    // public const SALE_POST_OVER_AND_ABOVE = [true, false];
    
    protected $casts = [
        // 'affect_grand_total' => 'boolean',
        'bill_sundry_amount_round_off' => 'boolean',
        'purchase_adjust_in_amount' => 'boolean',
        'purchase_adjust_in_party_amount' => 'boolean',
        'sale_adjust_in_amount' => 'boolean',
        'sale_adjust_in_party_amount' => 'boolean',
        'purchase_post_over_and_above' => 'boolean',
        'sale_post_over_and_above' => 'boolean',
    ];

    protected $fillable = [
        'uuid',
        'name',
        'print_name',
        'company_id',
        'bill_sundry_type',
        'calculation_type',
        'apply_on',
        // 'affect_grand_total',
        'bill_sundry_nature',
        'default_value',
        'bill_sundry_amount_round_off',
        'purchase_adjust_in_amount',
        'purchase_account_type',
        'purchase_account_id',
        'purchase_adjust_in_party_amount',
        'purchase_party_account_type',
        'purchase_party_account_id',
        'purchase_post_over_and_above',
        'sale_adjust_in_amount',
        'sale_account_type',
        'sale_account_id',
        'sale_adjust_in_party_amount',
        'sale_party_account_type',
        'sale_party_account_id',
        'is_active',
        'sale_post_over_and_above',
        'is_read_only',
        'code',
        'tds_category_id',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    // public function getRouteKeyName(): string
    // {
    //     return 'uuid';
    // }

    public function purchaseAccount()
    {
        return $this->belongsTo(Account::class, 'purchase_account_id');
    }

    public function purchasePartyAccount()
    {
        return $this->belongsTo(Account::class, 'purchase_party_account_id');
    }

    public function saleAccount()
    {
        return $this->belongsTo(Account::class, 'sale_account_id');
    }

    public function salePartyAccount()
    {
        return $this->belongsTo(Account::class, 'sale_party_account_id');
    }

    public function tdsCategory()
    {
        return $this->belongsTo(TdsCategory::class, 'tds_category_id');
    }
}
