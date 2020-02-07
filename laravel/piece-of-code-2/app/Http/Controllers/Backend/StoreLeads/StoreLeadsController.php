<?php

namespace App\Http\Controllers\Backend\StoreLeads;

use NornixCache;
use App\Models\Media;
use App\Models\Region;
use App\Models\Language;
use App\Models\Currency;
use Illuminate\Support\Arr;
use App\Models\StoreLeads\StoreLead;
use App\Http\Controllers\BackendController;
use App\Acme\Libraries\Datatables\Datatables;
use App\Acme\Libraries\Traits\Controllers\Holocaust;
use App\Jobs\RefreshNornixCache;
use App\Models\Users\User;
use App\Models\StoreLeads\AdmAgreement;
use App\Models\Stores\Store;
use App\Http\Requests\StoreLeads\SubmitStoreLeadsFormRequest;
use App\Http\Requests\StoreLeads\SubmitAgreementFormRequest;
use DB;
use Mail;
use Email;
use App\Jobs\SendEmail;
use Illuminate\Support\Facades\View;
use File;
use App\Models\Users\UserGroup as Group;
use Validator;
use App\Acme\Libraries\Http\FormRequest;


class StoreLeadsController extends BackendController {
	
	use Holocaust;
	
	/**
	 * @var string
	 */
	protected static $holocaustModel = StoreLead::class;

	public function __construct() {
		parent::__construct();

		$this->middleware('ajax', ['only' => 'indexDatatables']);
	}

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function index() {

		assets()->injectPlugin('bs-datepicker');
		$view_data['title'] = __t('titles.leads._global');
		
		return view('backend.storeleads.index', $view_data);
	}

	/**
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function create() {
		
		assets()->injectPlugin('bs-datepicker');

		return view('backend.storeleads.create', [
			'title' => __t('titles.leads._global'),
			'selectors' => ['region' => Region::all()],
			'selected' => ['region' => $this->request->getRegion(true)],
			'sales_rep_users' => $this->getUsersByGroupKey('wg_sales_rep'),
			'logged_in_user_group' => getLoggedInUserGroupKey($this->acl->getUser()->groups),
			'current_date' => date("Y-m-d")
		]);
	}

	public function Store(SubmitStoreLeadsFormRequest $request) {

		if($this->validateStoreURL($request)){
    		return $this->validateStoreURL($request);
    	}

		if ($request->has('sendAgmt') && $request->sendAgmt == 1) {

			if($this->validateDomainName($request)){
    			return $this->validateDomainName($request);
    		}

			$rules = $this->getSendAgreementFormFieldsRules();
			$messages = $this->getSendAgreementErrorMessages();

			if($this->validateAgreementFields($request, $rules, $messages)){
				return $this->validateAgreementFields($request, $rules, $messages);
			}
    	}   	
		
		$storeleads = new StoreLead($request->all());

		if ($storeleads->save()) {

			$email_assign_sales_rep = $this->assignSalesRepToLeadEmail($request);

			if (isset($request->agmt_term_months)) {
				$storeleads->agmt_term_months = getAgreementTermInMonths($request->agmt_term_months, $request->term_unit);
			}

			$logged_in_user_group = getLoggedInUserGroupKey($this->acl->getUser()->groups);
			if ($logged_in_user_group == 'wg_sales_rep') {
				$storeleads->sales_rep_id = $this->acl->getUser()->id;
			}
			$storeleads->save();

			if ($request->has('sendAgmt') && $request->sendAgmt == 1) {
				$send_agreement_status = $this->sendAgreement($request, $storeleads);

				if ($send_agreement_status) {
					flash()->success(__t('messages.success.send_agreement'));
				} else {
					flash()->error(__t('messages.error.general'));
					return redirect()->back();
				}
			}

			flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.lead')]));
			RefreshNornixCache::clearStoreLeadsCache();
			return redirect()->route('admin.storeleads.index');
		}
		
		flash()->error(__t('messages.error.saving'));
		return redirect()->back();
		
	}

	/**
	 * @param StoreLead $storeleads
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function show(StoreLead $storelead) {

		assets()->injectPlugin('bs-datepicker');

		$view_data['title'] = __t('titles.leads._global');
		//$view_data['title'] = __t('titles.subsystems.leads');

		$view_data['lead_item'] = $storelead;
		

		$logged_in_user_group = getLoggedInUserGroupKey($this->acl->getUser()->groups);

		if ($logged_in_user_group == 'wg_sales_rep' && $storelead->sales_rep_id != $this->acl->getUser()->id) {
			return redirect()->back();
		}

		$view_data['agmt_period'] = 'month';

        if ($storelead->agmt_term_months%12 == 0) {
            $view_data['lead_item']->agmt_term_months = $storelead->agmt_term_months/12;
            $view_data['agmt_period'] = 'year';
        }

		$signed_pdf_data = getSignedAgreementPdfUrlAndDate('store_lead_id', $storelead->id);
		$view_data['signed_pdf_url'] = $signed_pdf_data['agmt_signed_pdf_url'];
		$view_data['signed_date'] = $signed_pdf_data['agmt_signed_date'];


		$view_data['sales_rep_users'] = $this->getUsersByGroupKey('wg_sales_rep');
		$view_data['logged_in_user_group'] = getLoggedInUserGroupKey($this->acl->getUser()->groups);
		
		return view('backend.storeleads.show', $view_data);
	}

	public function update(SubmitStoreLeadsFormRequest $request, StoreLead $storelead){

		if($this->validateStoreURL($request)){
    		return $this->validateStoreURL($request);
    	}

		if ($request->has('sendAgmt') && $request->sendAgmt == 1) {

			$existing_domain = strtolower($storelead->domain);

			$new_domain = trim(strtolower($request->domain));
			if (empty($new_domain) || $existing_domain != $new_domain) {

				if($this->validateDomainName($request)){
					return $this->validateDomainName($request);
				}
			}

			$rules = $this->getSendAgreementFormFieldsRules();
			$messages = $this->getSendAgreementErrorMessages();
			
			if($this->validateAgreementFields($request, $rules, $messages)){
				return $this->validateAgreementFields($request, $rules, $messages);
			}
    	}

		$email_assign_sales_rep = $this->assignSalesRepToLeadEmail($request, $storelead->id);
		
		if ($storelead->update($request->all())) {

			if (isset($request->agmt_term_months)) {
				$storelead->agmt_term_months = getAgreementTermInMonths($request->agmt_term_months, $request->term_unit);
			}

			$logged_in_user_group = getLoggedInUserGroupKey($this->acl->getUser()->groups);
			if ($logged_in_user_group == 'wg_sales_rep') {
				$storelead->sales_rep_id = $this->acl->getUser()->id;
			}

			$storelead->save();

			if ($request->has('sendAgmt') && $request->sendAgmt == 1) {
				
				$send_agreement_status = $this->sendAgreement($request, $storelead);

				if ($send_agreement_status) {
					flash()->success(__t('messages.success.send_agreement'));
				} else {
					flash()->error(__t('messages.error.general'));
				}
			} else {
				flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.lead')]));
			}
		} else {
			flash()->error(__t('messages.error.saving'));
		}
		
		return redirect()->back();
	}

	public function sendAgreement($request, $storelead){

		$name = $request->name;
		$username = trim($request->email);
		$password = gen_random_string(20);

		$user = User::where('username', $username)->first();

		if (!$user) {

			$request->request->add(
				['group_ids' => ['4']]
			);

			$user = new User([
				'name' => $name,
				'username' => $username,
				'password' => $password
			]);

			$user->setStatus('active');

			if (!$user->saveRelationsFromRequest($request)->save()) {
	            return false;
	        }
		} else {
			$hash_password = User::encryptPassword($password);
			$user->password = $hash_password;
			$user->save();
		}

    	$request->request->add(['merchant_user_id' => $user->id]);
    	$request->request->add(['store_lead_id' => $storelead->id]);

    	$adm_agreement = AdmAgreement::where('store_lead_id', $storelead->id)->first();
    	if (isset($adm_agreement->id)) {
    		print_logs_app("is_adm_agreement_exists isset------->");
    		$adm_agreement->update($request->all());
    	} else {
    		print_logs_app("is_adm_agreement_exists CREATE------->");
    		$adm_agreement = new AdmAgreement($request->all());
    		if (!$adm_agreement->save()) {
    			return false;
    		}
    	}

    	if (isset($request->agmt_term_months)) {
            $adm_agreement->agmt_term_months = getAgreementTermInMonths($request->agmt_term_months, $request->term_unit);
        }

    	$email_template_type = $storelead->status;

		$adm_agreement->status = 'AgmtSent';
		$adm_agreement->save();

		$storelead->status = 'AgmtSent';
		$storelead->merchant_user_id = $user->id;
		$storelead->save();

		$wg_admin_panel_url = getServerIPwithPort().'/admin/auth/login';

    	$data = array(
    		'name' => $name,
    		'username' => $username,
    		'password' => $password,
    		'login_url' => $wg_admin_panel_url,
    		'prisfile_instructions_pdf_url' => getServerIPwithPort().'/docs/prisfile_instruction.pdf',
    		'lead_status' => $email_template_type,
    		'domain' => $storelead->domain,
    		'store_name' => $storelead->brand_name,
    		'contact_name' => $storelead->name
    	);

    	$subject = __t('emails.leads.agmtsigned.subject');

		if($email_template_type == 'AgmtSent'){
			$subject = __t('emails.leads.agmtsend.subject');
		}

		$jobs = [ new SendEmail($subject,
                    View::make('emails.agreement_send', $data),
                    [$username, $name]
                )];

		foreach ($jobs as $job) {
                dispatch($job)->onConnection('wg.emails');
        }

		RefreshNornixCache::clearStoreLeadsCache();
		RefreshNornixCache::clearAdmAgreementsCache();
		
		return true;
        
	}

	public function showAgreement($lead_agmt_id){

		assets()->injectPlugin('bs-datepicker');

		$view_data['lead_item'] = AdmAgreement::where('id', $lead_agmt_id)->first();

		$view_data['agmt_period'] = 'month';

        if ($view_data['lead_item']->agmt_term_months%12 == 0) {
               $view_data['lead_item']->agmt_term_months = $view_data['lead_item']->agmt_term_months/12;
               $view_data['agmt_period'] = 'year';
        }

		$view_data['terms_and_conditions_pdf_url'] = getServerIPwithPort(). __t('labels.terms_and_conditions_pdf_path'); //'/docs/terms_and_conditions.pdf';

		$view_data['title'] = __t('labels.forms.headings.confirmation_participation');
		$view_data['show_header'] = false;
		
		return view('backend.storeleads.agreement', $view_data);
	}


	public function signAgreement($agmt_id,SubmitAgreementFormRequest $request){

		$rules = [
            'sign_agmt_checkbox_1' => 'required',
            'sign_agmt_checkbox_2' => 'required',
        ];

        $messages = [
		    'sign_agmt_checkbox_1.required' => __t('messages.error.terms_and_conditions_checkbox_1'),
		    'sign_agmt_checkbox_2.required' => __t('messages.error.terms_and_conditions_checkbox_2'),
		];

		if($this->validateAgreementFields($request, $rules, $messages)){
			return $this->validateAgreementFields($request, $rules, $messages);
		}

		$domain = strtolower($request->domain);
		$agmt_signed_date = date('Y-m-d H:i:s');

		$output_dir_path = public_path().'/uploads/agreements/'.$domain;
		$output_filename_extension = '_GG_Avtal_'.date('m-d-Y_h:ia').'.pdf';
		$output_filename = $output_dir_path.'/'.$domain.$output_filename_extension;

		if (!file_exists($output_dir_path)) {

			$dir_status = File::makeDirectory($output_dir_path, 0775, true);
			if (!$dir_status) {
				flash()->error(__t('messages.error.general'));
				return redirect()->back();
			}
		}

		$region = $this->request->getRegion(true);
		
		//$store_details = $this->formatStoreCreationData($request, $region);		
		
		$agmt_term_months =  $request->agmt_term_months;
		if ($request->term_unit == 'year') {
			$agmt_term_months = $request->agmt_term_months * 12;
		}
		// Create or update Store from lead data & send emails
		//$store = Store::updateOrCreate(['domain' => $domain], $store_details);
		
        $store = Store::where('domain', $domain)->first();

        if (isset($store->id)) {
       		print_logs_app("StoreLeadsController - store already exists. Update the store.");
            $store_details = $this->formatStoreUpdateData($request, $region, $store->data('payex.prefix'));
       		Store::where('id', $store->id)->update($store_details);
        } else {
       		print_logs_app("StoreLeadsController - create the store");
               //$store = Store::create($store_details);
       		$store_details = $this->formatStoreCreationData($request, $region);
       		$store = Store::updateOrCreate(['domain' => $domain], $store_details);
       		// generate yb store api key
       		$store->yb_store_api_key = $store->data("payex.prefix"). "-".gen_random_string(40);
			$store->save();
        }

		if (isset($store->id)) {
			$store->saveRelationsFromRequest($request);
			
			if ($store->save()) {
				$store
					->setDefaultLanguage(Language::find($region->languages['0']->id))
					->setDefaultCurrency(Currency::find($region->currencies['0']->id));

				$agreement_data = AdmAgreement::find($agmt_id);

				$storelead_id = $agreement_data->store_lead_id;
				$merchant_user_id = $agreement_data->merchant_user_id;

				$agreement_data->status = 'AgmtSigned';
				$agreement_data->store_id = $store->id;
				$agreement_data->signed_date = $agmt_signed_date;
				$agreement_data->signed_pdf_url = $domain.'/'.$domain.$output_filename_extension;
				$agreement_data->save();

				$storelead = StoreLead::find($storelead_id);
				$storelead->store_id = $store->id;
				$storelead->status = 'AgmtSigned';
				$storelead->merchant_user_id = $merchant_user_id;
				$storelead->save();

				$store_user_relation = DB::table('store_user_relations')->where('store_id', $store->id)->where('user_id', $merchant_user_id)->get();

				if (!isset($store_user_relation[0]->id)) {
					
					DB::table('store_user_relations')->insert(
			            [
			                'store_id' => $store->id,
			                'user_id' => $merchant_user_id,
			            ]
			    	);
			    	RefreshNornixCache::clearStoreUserRelationsCache();
				}

				$agmt_end_date = getEndDate($request->agmt_start_date, $agmt_term_months);
				$terms_and_conditions_pdf_url = getServerIPwithPort(). __t('labels.terms_and_conditions_pdf_path'); //'/docs/terms_and_conditions.pdf';
				$agmt_signed_date_pdf = date("j F, Y", strtotime($agmt_signed_date));

				$agreement_email_data = array(
					'agreement_data' => $request,
					'agmt_end_date' => $agmt_end_date,
					'agmt_signed_date' => $agmt_signed_date,
					'terms_and_conditions_pdf_url' => $terms_and_conditions_pdf_url,
					'store_url' => get_store_url($store),
					'agmt_signed_date_pdf' => $agmt_signed_date_pdf,
					'wg_logo' => public_path().'/assets/images/logos-email/yb-logo-header.png'
				);

				$html = View::make('emails.save_merchant_agreement', $agreement_email_data)->render();
				$email_pdf = $this->generateAgreementPdf($html, $output_filename);

				$name = $request->name;
				$username = $request->email;

				$prisfile_instruction_pdf = public_path().'/docs/prisfile_instruction.pdf';
				$terms_and_conditions_pdf = public_path(). __t('labels.terms_and_conditions_pdf_path'); //'/docs/terms_and_conditions.pdf';

				$data = array(
					'name' => $name,
					'wg_header_logo' => getServerIPwithPort().'/assets/images/logos-email/yb-logo-header.png',
					'wg_footer_logo' => getServerIPwithPort().'/assets/images/logos-email/yb-logo-footer.png'
				);

				// Mail to Store Admin/ Merchant 
				$job = SendEmail::usingTranslationWithHtmlTemplate(__t('emails.leads.agmtsigned.subject'),
						View::make('emails.merchant_welcome', $data))->setRecipients([$username, $name])->attach([
						[
							'application/pdf',
							$username.'_agreement_confirmation.pdf',
							$email_pdf
						]
				]);

				dispatch($job)->onConnection('wg.emails');

				// Mail to Admin
				$this->sendSignedAgreementMailToAdmin($request, config('cms.emails.notifications'), 'Ggg', $username.'_agreement_confirmation.pdf', $email_pdf);

				// Mail to sales rep
				$sales_rep_details = $this->getSalesRepDetails($storelead->sales_rep_id);
				if ($sales_rep_details) {
					$this->sendSignedAgreementMailToAdmin($request, $sales_rep_details[0]['username'], $sales_rep_details[0]['name'], $username.'_agreement_confirmation.pdf', $email_pdf);
				}
				
				//Clear store product cache after save
		        RefreshNornixCache::afterStoreUpdate();
		        flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.store')]));

				return redirect()->route('admin.stores.show', $store->id);
			}
		}

		flash()->error(__t('messages.error.general'));
		return redirect()->back();
	}

	
	/**
     * @return mixed
     */
    public function indexDatatables($sales_rep_id = null) {

    	$storeleads = array();
    	if ($sales_rep_id) {
    		// $storeleads = StoreLead::where('sales_rep_id', $sales_rep_id)->get();
    		$storeleads = StoreLead::query()->where('sales_rep_id', $sales_rep_id);
    	}

        return Datatables::of($storeleads)
            ->editColumn('status', function (StoreLead $storelead) {

            	$find = ['RequestRecd', 'AgmtSent', 'AgmtSigned'];
				$replace = ['default', 'warning', 'success'];
				$status_label = str_replace($find, $replace, $storelead->status);

				$label_html = '<span class="label label-'.$status_label.'">' . __t(sprintf("labels.tables.statuses.%s", $storelead->status), [], $storelead->status) . '</span>';
                return $label_html;
            })
            ->rawColumns(['checkbox','status'])->make(true);
    }


    public function getUsersByGroupKey($user_group_key){

    	$group = Group::key($user_group_key)->first();
    	$users = User::inside($group->id)->get();

    	if (!count($users) > 0) {
			$users = array();
		}

		return $users;
    }


    public function getSendAgreementFormFieldsRules(){

    	$rules = [
    		'brand_name' => 'required',
    		'store_url' => 'required',
            'name' => 'required',
            'title' => 'required',
            'phone' => 'required',
            'company_name' => 'required',
            'organisation_id' => 'required',
            'organisation_address' => 'required',
            'organisation_zipcode' => 'required',
            'organisation_city' => 'required',
            'organisation_phone' => 'required',
            'customer_service_email' => 'required',
            'customer_service_phone' => 'required',
            'order_to_merchant_email' => 'required',
            'agmt_provision' => 'required',
            'marketing_contribution' => 'required',
            'extra_bonus' => 'required',
            'agmt_start_date' => 'required',
            'store_items_count' => 'required'
        ];

        return $rules;
    }

    public function getSendAgreementErrorMessages(){

    	$is_required = ' '.__t('messages.error.is_required');

    	$messages = [
    		'brand_name.required' => __t('labels.forms.stores.name').$is_required,
    		'store_url.required' => __t('labels.forms.stores.name').$is_required,
    		'name.required' => __t('labels.forms.name').$is_required,
            'title.required' => __t('labels.forms.title').$is_required,
            'phone.required' => __t('labels.forms.phone').$is_required,
            'company_name.required' =>  __t('labels.forms.stores.company_name').$is_required,
            'organisation_id.required' => __t('labels.forms.organisation_id').$is_required,
            'organisation_address.required' => __t('labels.forms.organisation_address').$is_required,
            'organisation_zipcode.required' => __t('labels.forms.organisation_zipcode').$is_required,
            'organisation_city.required' => __t('labels.forms.organisation_city').$is_required,
            'organisation_phone.required' => __t('labels.forms.organisation_phone').$is_required,
            'customer_service_email.required' => __t('labels.forms.customer_service_email').$is_required,
            'customer_service_phone.required' => __t('labels.forms.customer_service_phone').$is_required,
            'order_to_merchant_email.required' => __t('labels.forms.order_to_merchant_email').$is_required,
            'agmt_provision.required' => __t('labels.forms.agmt_provision').$is_required,
            'marketing_contribution.required' => __t('labels.forms.marketing_contribution').$is_required,
            'extra_bonus.required' => __t('labels.forms.extra_bonus').$is_required,
            'agmt_start_date.required' => __t('labels.forms.start_date').$is_required,
            'store_items_count.required' => __t('labels.forms.store_items_count').$is_required
    	];

    	return $messages;
    }


    public function assignSalesRepToLeadEmail($request, $lead_id=null){

    	if ($request->has('sales_rep_id')) {

    		$new_sales_rep_id = $request->sales_rep_id;
    		$is_assign_sales_rep = false;

    		if (isset($new_sales_rep_id) && $new_sales_rep_id != 0 && is_null($lead_id))  {
    			$is_assign_sales_rep = true;
    			$is_remove_sales_rep = false;
    		} else {

    			if(isset($lead_id)) {
    				$get_lead_details = StoreLead::where('id', $lead_id)->first();
	    			$old_sales_rep_id = $get_lead_details->sales_rep_id;

	    			if ($old_sales_rep_id == $new_sales_rep_id) {
		    			return true;
		    		} else {

		    			if ($old_sales_rep_id == 0 && $new_sales_rep_id != 0) {
		    				$is_assign_sales_rep = true;
    						$is_remove_sales_rep = false;
	    				} else if ($old_sales_rep_id != 0 && $new_sales_rep_id != 0) {

	    					$is_assign_sales_rep = true;
    						$is_remove_sales_rep = true;
	    				} else if($old_sales_rep_id != 0 && $new_sales_rep_id == 0) {
	    					$is_assign_sales_rep = false;
    						$is_remove_sales_rep = true;
	    				}

	    				if ($is_remove_sales_rep) {
	    					$remove_sales_rep_user = User::where('id', $old_sales_rep_id)->get();

	    					if(isset($remove_sales_rep_user)){
	    						dispatch(SendEmail::usingTranslation('leads.remove_sales_rep', [
									'store_url' => $request->brand_name
									], 'backend')->setRecipients([$remove_sales_rep_user[0]['username'], $remove_sales_rep_user[0]['name']])
	    						)->onConnection('wg.emails');
	    					}
	    				}
		    		}
		    	}
    		}

    		if ($is_assign_sales_rep) {
    			$assign_sales_rep_user = User::where('id', $new_sales_rep_id)->get();

    			if (isset($assign_sales_rep_user)) {

					dispatch(SendEmail::usingTranslation('leads.assign_sales_rep', [
						'store_url' => $request->brand_name
					], 'backend')->setRecipients([$assign_sales_rep_user[0]['username'], $assign_sales_rep_user[0]['name'] ]))->onConnection('wg.emails');
	    		}
    		}
    		
			return true;
    	}

    	return false;
    }


    public function sendSignedAgreementMailToAdmin($request, $to_address, $receiver_name, $file_name, $content = null){

    	$job = SendEmail::usingTranslation('leads.sign_agreement_admin_sales_rep', [
					'store_url' => $request->brand_name
				], 'backend')->setRecipients([$to_address, $receiver_name])->attach([
					[
						'application/pdf',
						$file_name,
						$content
					]
				]);

		dispatch($job)->onConnection('wg.emails');
    }


    public function getSalesRepDetails($sales_rep_id){

    	$get_sales_user_details = User::where('id', $sales_rep_id)->get();
    	if (isset($get_sales_user_details[0]['username']) && !empty($get_sales_user_details[0]['username'])) {
    		return $get_sales_user_details;
    	} 

    	return false;
    }


    public function generateAgreementPdf($html, $output_filename){

    	$snappy = app('snappy.pdf');
		$snappy->setOption('encoding', 'utf-8');
		$snappy->setOption('margin-top', '0');
		$snappy->setOption('margin-right', '0');
		$snappy->setOption('margin-bottom', '0');
		$snappy->setOption('margin-left', '0');
		$snappy->generateFromHtml($html, $output_filename);

		$pdf_content = $snappy->getOutputFromHtml($html);

    	return $pdf_content;
    }


    public function formatStoreUpdateData($request, $region, $payex_prefix){

    	$store_details = array();

    	if ($request->has('domain')) {

			//$store_details['enabled'] = 0;
			//$store_details['featured'] = 0;
			//$store_details['sync'] = 0;

			$store_details['region_id'] = $request->region_id;;
			$store_details['vat'] = $request->vat;
			$store_details['name'] = $request->brand_name;
			$store_details['domain'] = $request->domain;

			//$store_details['languages']['default'] = $region->languages['0']->id;
			//$store_details['languages']['enabled'][0] = $region->languages['0']->id;

			//$store_details['currencies']['default'] = $region->currencies['0']->id;
			//$store_details['currencies']['enabled'][0] = $region->currencies['0']->id;
			
			$store_details['data'] = array();
			$store_details['data']['brand_name'] = $request->company_name;
			
			$store_details['data']['details']['store_url'] = $request->store_url;
			$store_details['data']['details']['organisation_id'] = $request->organisation_id;
			$store_details['data']['details']['software_provider'] = $request->software_provider;
			$store_details['data']['details']['payment_provider'] = $request->payment_provider;

			$store_details['data']['payex']['bank_acc']['type'] = $request->store_bank_account_type;
			$store_details['data']['payex']['bank_acc']['number'] = $request->store_bank_account_number;
			$store_details['data']['payex']['prefix'] = $payex_prefix;

			$store_details['data']['contact']['title'] = $request->title;
			$store_details['data']['contact']['name'] = $request->name;
			$store_details['data']['contact']['email'] = $request->email;
			$store_details['data']['contact']['phone'] = $request->phone;

			$store_details['data']['support']['email'] = $request->customer_service_email;
			$store_details['data']['support']['phone'] = $request->customer_service_phone;
			$store_details['data']['notifications']['email'] = $request->order_to_merchant_email;
			$store_details['data'] = serialize($store_details['data']);

		}

    	return $store_details;
    }

    public function formatStoreCreationData($request, $region){

        $store_details = array();

        if ($request->has('domain')) {

            $store_details['enabled'] = 0;
            $store_details['featured'] = 0;
            $store_details['sync'] = 0;

            $store_details['region_id'] = $request->region_id;;
            $store_details['vat'] = $request->vat;
            $store_details['name'] = $request->brand_name;
            $store_details['domain'] = $request->domain;

            $store_details['languages']['default'] = $region->languages['0']->id;
            $store_details['languages']['enabled'][0] = $region->languages['0']->id;

            $store_details['currencies']['default'] = $region->currencies['0']->id;
            $store_details['currencies']['enabled'][0] = $region->currencies['0']->id;

            $store_details['data']['brand_name'] = $request->company_name;
            
            $store_details['data']['details']['store_url'] = $request->store_url;
            $store_details['data']['details']['organisation_id'] = $request->organisation_id;
            $store_details['data']['details']['software_provider'] = $request->software_provider;
            $store_details['data']['details']['payment_provider'] = $request->payment_provider;

            $store_details['data']['payex']['bank_acc']['type'] = $request->store_bank_account_type;
            $store_details['data']['payex']['bank_acc']['number'] = $request->store_bank_account_number;

            $store_details['data']['contact']['title'] = $request->title;
            $store_details['data']['contact']['name'] = $request->name;
            $store_details['data']['contact']['email'] = $request->email;
            $store_details['data']['contact']['phone'] = $request->phone;

            $store_details['data']['support']['email'] = $request->customer_service_email;
            $store_details['data']['support']['phone'] = $request->customer_service_phone;
            $store_details['data']['notifications']['email'] = $request->order_to_merchant_email;

        }

        return $store_details;
    }

    public function assignLeadToSalesRep(FormRequest $request)
    {
    	if ($request->has('sales_rep_id') && $request->has('storelead_id')) {
    		$storelead = StoreLead::find($request->storelead_id);
    		$storelead->sales_rep_id = $request->sales_rep_id;
    		
    		if ($storelead->save()) {
    			$this->assignSalesRepToLeadEmail($request, $request->storelead_id);
    		}

    		flash()->success(__t('messages.success.assigned'));

    	} else {
    		flash()->error(__t('messages.error.general'));
    	}

    	return redirect()->back();
    }

    public function validateDomainName($request)
    {
    	$rules = ['domain' => 'required|unique:store_leads'];
    	$messages = [
    		'domain.required' => __t('labels.forms.domain').' '.__t('messages.error.is_required'),
    		'domain.unique' => __t('messages.error.domain_exists'),
    	];

    	$validator = Validator::make($request->all(), $rules, $messages);
		
		if ($validator->fails()) {
		    return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
		}

		return false;
    }

    public function validateStoreURL($request)
    {

    	$rules = ['store_url' => 'required|max:255'];
    	$messages = ['store_url.required' => __t('messages.error.store_url_required')];

    	$validator = Validator::make($request->all(), $rules, $messages);
		
		if ($validator->fails()) {
		    return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
		}

		return false;
    }

    public function validateAgreementFields($request, $rules, $messages){

    	$validator = Validator::make($request->all(), $rules, $messages);
		
		if ($validator->fails()) {
		    return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
		}

		return false;
    }

    public function leadsPagination()
    {
    	$user_id = $this->acl->getUser()->id;
		$user_groups = $this->acl->getUser()->groups;

		$logged_in_user_group = array();

		foreach ($user_groups as $key => $group) {
			$logged_in_user_group[] = $group->key;
		}

		if (isset($_GET['search']['value']) && $_GET['search']['value'] != '') {

			$search_keyword = '%'.trim($_GET['search']['value']).'%';
			$keyword = [$search_keyword];
		}

		if(in_array('wg_admin', $logged_in_user_group) || in_array('wg_sales', $logged_in_user_group)){

			if (isset($_GET['search']['value']) && $_GET['search']['value'] != '') {
				
				$query = StoreLead::select('store_leads.*','users.username as sales_rep_email')->leftjoin('users', 'users.id', '=', 'store_leads.sales_rep_id')
					->whereRaw('store_leads.store_url like ? ', $keyword)
					->orWhereRaw('store_leads.brand_name like ? ', $keyword)
					->orWhereRaw('store_leads.email like ?', $keyword)
				    ->orWhereRaw('store_leads.agmt_start_date like ?', $keyword)
				    ->orWhereRaw('store_leads.agmt_provision like ?', $keyword)
				    ->orWhereRaw('store_leads.marketing_contribution like ?', $keyword)
				    ->orWhereRaw('store_leads.status like ?', $keyword)
				    ->orWhereRaw('store_leads.updated_at like ?', $keyword)
					->orWhereRaw('users.username like ?', $keyword)
					->get();
			} else {
				$query = StoreLead::select('store_leads.*','users.username as sales_rep_email')->leftjoin('users', 'users.id', '=', 'store_leads.sales_rep_id');
			}

			return Datatables::of($query)
	            ->editColumn('status', function (StoreLead $storelead) {

	            	$find = ['RequestRecd', 'AgmtSent', 'AgmtSigned'];
					$replace = ['default', 'warning', 'success'];
					$status_label = str_replace($find, $replace, $storelead->status);

					$label_html = '<span class="label label-'.$status_label.'">' . __t(sprintf("labels.tables.statuses.%s", $storelead->status), [], $storelead->status) . '</span>';
	                return $label_html;
	            })
	            ->editColumn('marketing_contribution', function (StoreLead $storelead) {
	            	if ($storelead->marketing_contribution_type == 'per_month') {
	            		$type = __t("labels.forms.per_month");
	            	} else {
	            		$type = __t("labels.forms.per_year");
	            	}

	            	return $storelead->marketing_contribution." (".$type.")";
	            })
	            ->rawColumns(['checkbox','status'])->make(true);

		} else if (in_array('wg_sales_rep', $logged_in_user_group)) {

			$query = StoreLead::select('store_leads.*','users.username as sales_rep_email')
					->join('users', 'users.id', '=', 'store_leads.sales_rep_id')
					->where('sales_rep_id', $user_id);

			return Datatables::of($query)
	            ->editColumn('status', function (StoreLead $storelead) {

	            	$find = ['RequestRecd', 'AgmtSent', 'AgmtSigned'];
					$replace = ['default', 'warning', 'success'];
					$status_label = str_replace($find, $replace, $storelead->status);

					$label_html = '<span class="label label-'.$status_label.'">' . __t(sprintf("labels.tables.statuses.%s", $storelead->status), [], $storelead->status) . '</span>';
	                return $label_html;
	            })
	            ->editColumn('marketing_contribution', function (StoreLead $storelead) {
	            	if ($storelead->marketing_contribution_type == 'per_month') {
	            		$type = __t("labels.forms.per_month");
	            	} else {
	            		$type = __t("labels.forms.per_year");
	            	}

	            	return $storelead->marketing_contribution." (".$type.")";
	            })
	            ->blacklist(['sales_rep_email'])
	            ->rawColumns(['checkbox','status'])->make(true);
		}
    }
}
