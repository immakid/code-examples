<?php

namespace App\Http\Controllers\Backend\Stores;

use NornixCache;
use App\Models\Media;
use App\Models\Region;
use App\Models\Language;
use App\Models\Currency;
use App\Models\FinancialTransactions;
use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use App\Models\Users\UserGroup;
use App\Http\Controllers\BackendController;
use App\Acme\Libraries\Datatables\Datatables;
use App\Acme\Libraries\Traits\Controllers\Holocaust;
use App\Http\Requests\Stores\SubmitStoreFormRequest;
use App\Jobs\RefreshNornixCache;
use App\Models\StoreLeads\AdmAgreement;
use App\Models\StoreLeads\StoreLead;


class StoresController extends BackendController {

	use Holocaust;
	
	/**
	 * @var string
	 */
	protected static $holocaustModel = Store::class;

	public function __construct() {
		parent::__construct();

		$this->middleware('ajax', ['only' => 'indexDatatables']);
	}

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function index() {
		
		$user_id = $this->acl->getUser()->id;
		$agreement_data = AdmAgreement::select('*')->where('merchant_user_id', $user_id)->where('status','AgmtSent')->orderBy('id', 'DESC')->get();

		if (isset($agreement_data) && !empty($agreement_data)) {
			foreach ($agreement_data as $key => $value) {
				
				if (isset($value->id)) {
					return redirect()->route('admin.storeleads.agreement', $value->id);
				}
			}
		}

		return view('backend.stores.index');
	}

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function create() {
		assets()->injectPlugin('bs-fileupload');

		return view('backend.stores.create', [
			'selectors' => ['region' => Region::all()],
			'selected' => ['region' => $this->request->getRegion(true)]
		]);
	}

	/**
	 * @param Store $store
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function show(Store $store) {
        assets()->injectPlugin(['bs-fileupload', 'summernote']);

        $agmt_signed_pdf_data = getSignedAgreementPdfUrlAndDate('store_id', $store->id);

        $financial_transactions = FinancialTransactions::where("store_id",$store->id)->get();
		$data = [
			'item' => $store,
			'financial_transactions' => $financial_transactions,
			'users' => [
				'list' => $store->users,
				'groups' => UserGroup::all()->reject(function (UserGroup $model) {
					return !(in_array($model->key, config('acl.groups.list.store')));
				})
			],
			'price_file' => ['object' => false, 'media' => false],
			'orders_count' => $this->orderRepository
				->setCriteria($store->getOrdersCriteria())
				->count(),
			'agmt_signed_pdf_url' => $agmt_signed_pdf_data['agmt_signed_pdf_url'],
			'agmt_signed_date' => $agmt_signed_pdf_data['agmt_signed_date'],
		];

		$priceFile = $store->priceFile;
		if ($priceFile && !$priceFile->isRemote && $priceFile->hasMandatoryMappings) {
			if (user_can('manage_stores_price_files')) {

				$data['price_file']['object'] = $priceFile;
				$data['price_file']['media'] = Media::fromFile($priceFile->localFileName);
			}
		}

		return view('backend.stores.show', $data);
	}

	/**
	 * @param SubmitStoreFormRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function store(SubmitStoreFormRequest $request) {

		$store = new Store($request->all());
		$store->saveRelationsFromRequest($request);
		
		if ($store->save()) {

			$store
				->setDefaultLanguage(Language::find($request->input('languages.default')))
				->setDefaultCurrency(Currency::find($request->input('currencies.default')))
				->savePhotoFromRequest($request, [
					'logo' => [
						config('cms.sizes.thumbs.store.logo'),
						config('cms.sizes.thumbs.store.logo-blog')
					],
					'logo-black' => [
						config('cms.sizes.thumbs.store.logo'),
						config('cms.sizes.thumbs.store.logo-home'),
						config('cms.sizes.thumbs.store.logo-blog')
					],
					'featured' => config('cms.sizes.thumbs.store.featured-image'),
                    'banner' => config('cms.sizes.thumbs.store.banner'),
				], [
					'logo' => [
						1 => 'exact' // logo-blog
					],
					'logo-black' => [
						1 => 'exact', // logo-home
						2 => 'exact' // logo-blog
					]
				]);

				// generate yb store api key
	       		$store->yb_store_api_key = $store->data("payex.prefix"). "-".gen_random_string(40);
				$store->save();

			//Clear store product cache after save
            RefreshNornixCache::afterStoreUpdate();flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.store')]));

			return redirect()->route('admin.stores.show', $store->id);
		}

		flash()->error(__t('messages.error.saving'));

		return redirect()->back();
	}

    /**
     * @param SubmitStoreFormRequest $request
     * @param Store $store
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(SubmitStoreFormRequest $request, Store $store)
    {

        /*
         * if ($request->price_round == null) {
            $store->price_round = 0;
        } else {
            $store->price_round = $request->price_round;
        }
        $store->price_delimiter = $request->price_delimiter;
        */

        $store_lead = StoreLead::where('store_id', $store->id)->first();

        if (!empty($store_lead)) {
        	
	    	if ($request->has('data')) {
	    		$this->updateLeadOrAgreementData($store_lead, $store, $request);

	    		$adm_agreement_data = AdmAgreement::where('store_lead_id', $store_lead->id)
	    								->where('store_id', $store->id)
	    								->first();

	    		if(!empty($adm_agreement_data) && $adm_agreement_data->status == "AgmtSent") {
	    			$this->updateLeadOrAgreementData($adm_agreement_data, $store, $request);
	    		}

	    	}
	    }

        if ($store->updateFromMultilingualRequest($request)) {

			$store
				->setDefaultLanguage(Language::find($request->input('languages.default')))
				->setDefaultCurrency(Currency::find($request->input('currencies.default')))
				->savePhotoFromRequest($request, [
					'logo' => [
						config('cms.sizes.thumbs.store.logo'),
						config('cms.sizes.thumbs.store.logo-blog')
					],
					'logo-black' => [
						config('cms.sizes.thumbs.store.logo'),
						config('cms.sizes.thumbs.store.logo-home'),
						config('cms.sizes.thumbs.store.logo-blog')
					],
					'featured' => config('cms.sizes.thumbs.store.featured-image'),
                    'banner' => config('cms.sizes.thumbs.store.banner'),
				], [
					'logo' => [
						1 => 'exact'
					],
					'logo-black' => [
						1 => 'exact',
						2 => 'exact'
					]
				]);

			//Clear store product cache after update
            RefreshNornixCache::afterStoreUpdate();

			flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.store')]));
		} else {
			flash()->error(__t('messages.error.saving'));
		}

        return redirect()->back();
    }


	/**
	 * @return mixed
	 */
	public function indexDatatables() {

		$table = get_table_name(Store::class);
		$query = Store::with('region')
			->select(sprintf("%s.*", $table))
			->withCount('products');

		if ($this->acl->belongsToOneOf(config('acl.groups.list.store'))) {
			$query = $query->whereIn('id', Arr::pluck($this->acl->getUser()->stores->toArray(), 'id'));
		}

		$calcMappedCategories = function (Region $region, array $storeIds) {

			static $regions = [];

			if (!$ids = Arr::get($regions, $region->id)) {

				$mappings = NornixCache::setRegion($region)
					->setNamespace('categories')
					->setMethod('mapping')
					->read();

				$ids = $regions[$region->id] = Arr::flatten(Arr::get($mappings, $region->id, []));
			}

			return count($storeIds) - count(array_diff(array_keys($storeIds), array_unique(Arr::flatten($ids))));
		};

		return Datatables::of($query)
			->addColumn('prefix', function (Store $model) {
				return $model->data('payex.prefix', '/');
			})
			->addColumn('categories_stats', function (Store $model) use ($calcMappedCategories) {

//				$categories = array_filter(Arr::pluck($model->categories, 'parent_id', 'id'), function ($value) {
//					return is_null($value);
//				});
//
//				return sprintf("%d/%d", $calcMappedCategories($model->region, $categories), count($categories));
            })
            ->editColumn('enabled', function (Store $model) {
                return $model->enabled ?
                    '<span class="label label-success">' . __t('labels.tables.enabled') . '</span>' :
                    '<span class="label label-danger">' . __t('labels.tables.disabled') . '</span>';
            })
            ->editColumn('domain', function (Store $model) {
                $url = get_store_url($model);

                return sprintf("%s (<a href='%s' target='_self'>%s</a>)", $model->domain, $url, $url);
            })
            ->rawColumns(['checkbox', 'domain', 'enabled'])
            ->make(true);
    }

    public function updateLeadOrAgreementData($lead_or_agmt_obj, $store_obj, $request)
    {
		
		if( $store_obj->vat != trim($request->vat) ) {

			if ( isset($request->vat) ) {
				$lead_or_agmt_obj->vat = trim($request->vat);
			}
		}

    	if( $store_obj->data['contact']['title'] != trim($request->data['contact']['title']) ) {

			if ( isset($request->data['contact']['title']) ) {
				$lead_or_agmt_obj->title = trim($request->data['contact']['title']);
			}
		}

		if( $store_obj->data['contact']['name'] != trim($request->data['contact']['name']) ) {

			if (isset($request->data['contact']['name'])) {
				$lead_or_agmt_obj->name = trim($request->data['contact']['name']);
			}
		}


		if( $store_obj->data['contact']['email'] != trim($request->data['contact']['email']) ) {

			if ( isset($request->data['contact']['email']) ) {
				$lead_or_agmt_obj->email = trim($request->data['contact']['email']);
			}
		}

		if( $store_obj->data['contact']['phone'] != trim($request->data['contact']['phone']) ) {

			if ( isset($request->data['contact']['phone']) ) {
				$lead_or_agmt_obj->phone = trim($request->data['contact']['phone']);
			}
		}

		if( $store_obj->data['support']['email'] != trim($request->data['support']['email']) ) {
			if (isset($request->data['support']['email'])) {
				$lead_or_agmt_obj->customer_service_email = trim($request->data['support']['email']);	
			}
		}

		if( $store_obj->data['support']['phone'] != trim($request->data['support']['phone']) ) {
			if (isset($request->data['support']['phone'])) {
				$lead_or_agmt_obj->customer_service_phone = trim($request->data['support']['phone']);
			}
		}

		if( $store_obj->data['notifications']['email'] != trim($request->data['notifications']['email']) ) {
			if (isset($request->data['notifications']['email'])) {
				$lead_or_agmt_obj->order_to_merchant_email = trim($request->data['notifications']['email']);
			}
		}

		if( $store_obj->data['brand_name'] != trim($request->data['brand_name']) ) {

			if (isset($request->data['brand_name'])) {
				$lead_or_agmt_obj->company_name = trim($request->data['brand_name']);	
			}
		}

		if( $store_obj->data['details']['software_provider'] != trim($request->data['details']['software_provider']) ) {

			if (isset($request->data['details']['software_provider'])) {
				$lead_or_agmt_obj->software_provider = trim($request->data['details']['software_provider']);
			}
		}

		if( $store_obj->data['details']['payment_provider'] != trim($request->data['details']['payment_provider']) ) {

			if (isset($request->data['details']['payment_provider'])) {
				$lead_or_agmt_obj->payment_provider = trim($request->data['details']['payment_provider']);	
			}
		}

		$lead_or_agmt_obj->save();
    }
}
