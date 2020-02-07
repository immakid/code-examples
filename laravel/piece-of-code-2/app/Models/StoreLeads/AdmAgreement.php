<?php

namespace App\Models\StoreLeads;

use App;
use StoreFacade;
use App\Models\Config;
use App\Models\Region;
use App\Models\Address;
use App\Models\Users\User;
use Illuminate\Support\Arr;
use App\Models\Users\UserGroup;
use App\Acme\Repositories\Criteria\Scope;
use App\Acme\Interfaces\Eloquent\Mediable;
use App\Acme\Repositories\Criteria\WhereHas;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Acme\Interfaces\Eloquent\Multilingual;
use App\Acme\Interfaces\Eloquent\Serializable;
use App\Acme\Interfaces\Eloquent\Translatable;
use App\Acme\Extensions\Database\Eloquent\Model;
use App\Acme\Libraries\Traits\Eloquent\Serializer;
use App\Acme\Libraries\Traits\Eloquent\Categorizer;
use App\Acme\Libraries\Traits\Eloquent\MediaManager;
use App\Acme\Libraries\Traits\Eloquent\RelationManager;
use App\Acme\Libraries\Traits\Eloquent\CurrencyChooser;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use App\Acme\Libraries\Traits\Eloquent\Languages\Translator;
use App\Acme\Libraries\Traits\Eloquent\Languages\Chooser as LanguageChooser;

/**
 * App\Models\StoreLeads\AdmAgreement
 *
 * @property int $id
 * @property string $title
 * @property string $domain
 * @property string $name
 * @property string $email
 */
class AdmAgreement extends Model {

	use RelationManager,
		SoftDeletes;

	/**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'adm_agreements';
    
	/**
	 * @var array
	 */
	protected $fillable = [
        'status',
        'merchant_user_id',
        'store_lead_id',
        'store_id',
        'region_id',
		'vat',
		'title',
		'name',
		'phone',
		'customer_service_email',
		'customer_service_phone',
		'email',
		'order_to_merchant_email',
		'brand_name',
		'domain',
		'prisfile_url',
		'organisation_id',
		'company_name',
		'software_provider',
		'payment_provider',
		'agmt_start_date',
		'organisation_address',
		'organisation_zipcode',
		'organisation_city',
		'organisation_phone',
		'store_bank_account_type',
		'store_bank_account_number',
		'agmt_provision',
		'marketing_contribution',
		'extra_bonus',
		'agmt_term_months',
		'is_apply_cancellation_charge',
		'is_pay_return_shipping_charge',
		'store_started_year',
		'store_items_count',
		'store_online_turnover',
		'store_safetly_cert',
		'sales_rep_id',
		'cancellation_charges',
		'store_url'
	];

}