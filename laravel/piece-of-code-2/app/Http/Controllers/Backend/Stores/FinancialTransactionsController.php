<?php

namespace App\Http\Controllers\Backend\Stores;

use App\Models\FinancialTransactions;
use App\Models\Career;
use App\Models\Currency;
use App\Models\Price;
use Illuminate\Support\Arr;
use App\Models\Stores\Store;
use App\Http\Controllers\BackendController;
use App\Http\Requests\Subsystems\SubmitFinancialTransactionsFiltersFormRequest;

class FinancialTransactionsController extends BackendController {

       /**
        * @param Store $store
        * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
        */
       public function index(Store $store) {

                assets()->injectPlugin('bs-datepicker');
                $financial_transactions = FinancialTransactions::where("store_id",$store->id)->get();
                if($view_data = $this->validateFinancialTranslations($financial_transactions,$store)){
                        return view('backend._subsystems.financial-transactions.index',$view_data);
                }
                return view('backend._subsystems.financial-transactions.index',$this->generateViewData($financial_transactions, $store));
       }

        /**
         * @param Store $store
         * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
         */
        public function applyFilters(SubmitFinancialTransactionsFiltersFormRequest $request, Store $store) {

                assets()->injectPlugin('bs-datepicker');
                $filter_data = $request->input('filter_data', []);
                $financial_transactions = array();
                $output_filter_data = array();

                foreach ($filter_data as $key => $value) {

                        switch ($value) {
                                case '0':
                                case '7':
                                case '90':
                                case '360':
                                        // print_logs_app("Applying last $value days");
                                        $output_filter_data = array($value);
                                        $date = \Carbon\Carbon::today()->subDays($value);
                                        $financial_transactions = FinancialTransactions::where("store_id",$store->id)->where('created_at', '>=', $date)->get();
                                        break;
                                case '30':
                                        $output_filter_data = array($value);
                                        $financial_transactions = FinancialTransactions:: where("store_id",$store->id)->whereMonth('created_at', '=', \Carbon\Carbon::now()->subMonth()->month)->whereYear('created_at', '=', \Carbon\Carbon::now()->year)->get();
                                        break;
                                case '60':
                                        $output_filter_data = array($value);
                                        $valid_from = \Carbon\Carbon::today()->subDays($value);
                                        $valid_until = \Carbon\Carbon::today()->subDays('30');
                                        $financial_transactions =FinancialTransactions::where("store_id",$store->id)->whereBetween('created_at', [$valid_from, $valid_until])->get();
                                        break;
                                case 'custom':
                                        $valid_from = $filter_data['valid_from'];
                                        $valid_until = $filter_data['valid_until'];
                                        if (isset($valid_from) && isset($valid_until)) {

                                                        $output_filter_data = $filter_data;
                                                        // print_logs_app("Applying from and to");
                                                        $financial_transactions =FinancialTransactions::where("store_id",$store->id)->whereBetween('created_at', [$valid_from, $valid_until])->get();
                                        }
                                        break;
                                case 'all':
                                        $output_filter_data = array($value);
                                        $financial_transactions = FinancialTransactions::where("store_id",$store->id)->get();
                                        break;
                                default:
                                        //Do Nothing
                                        break;
                        }
                }

                if($view_data = $this->validateFinancialTranslations($financial_transactions,$store,$filter_data)){
                        return view('backend._subsystems.financial-transactions.index',$view_data);
                }

                return view('backend._subsystems.financial-transactions.index', array_merge($this->generateViewData($financial_transactions,$store),['filter_data' => $output_filter_data]));
        }

        public function validateFinancialTranslations($financial_transactions, Store $store, $filter_data = [])
        {
                if (count($financial_transactions) < 1) {
                        return [
                        'no_records_found' => true,
                        'summary' => [
                                'sum_of_sales_price'=> ['title' => __t('titles.store.total_sales'), 'amount' => ['credit' => 0, 'debit' => 0]],
                                'sum_of_wg_commission'=> ['title' => __t('titles.store.wg_commission'), 'amount' => ['credit' => 0, 'debit' => 0]],
                                'sum_of_exclude_wg_commission'=> ['title' => __t('titles.store.total_sales_exclude_wg_comsn'), 'amount' => ['credit' => 0, 'debit' => 0]],
                                'sum_of_vat'=> ['title' => __t('titles.store.vat'), 'amount' => ['credit' => 0, 'debit' => 0]],
                                'sum_of_exclude_wg_commission_vat'=> ['title' => __t('titles.store.total_sales_exclude_wg_comsn_vat'), 'amount' => ['credit' => 0, 'debit' => 0]],
                                'sum_of_vat_commission'=> ['title' => __t('titles.store.total_sales_vat_comsn'), 'amount' => ['credit' => 0, 'debit' => 0]],
                                'payable_to_store'=> ['title' => __t('titles.store.total_sales_to_store'), 'amount' => ['credit' => 0, 'debit' => 0]],
                                ],
                        'store' => $store,
                        'filter_data' => $filter_data
                        ];
                } else {
                        return null;
                }
        }

        public function generateViewData($financial_transactions, Store $store)
        {
                foreach ($financial_transactions as $key => $transaction) {
                        $orders[$key] = $transaction->getOrderID($transaction->order_id);
                }

                //Credit totals
                $credit_summary_details = $financial_transactions->where("type","credit");
                $credit_sum_of_sales_price = $credit_summary_details->sum('total_sales_price');
                $credit_sum_of_exclude_wg_commission = $credit_summary_details->sum('exclude_wg_commission');
                $credit_sum_of_exclude_wg_commission_vat = $credit_summary_details->sum('exclude_vat');
                $credit_sum_of_vat = $credit_summary_details->sum('vat');
                $credit_sum_of_wg_commission = $credit_summary_details->sum('wg_commission');
                $credit_sum_of_vat_commission = $credit_summary_details->sum('vat_commission');;
                $credit_payable_to_store = $credit_summary_details->sum('payable_to_store');;

                //Debit totals
                $debit_summary_details = $financial_transactions->where("type","debit");
                $debit_sum_of_sales_price = $debit_summary_details->sum('total_sales_price');
                $debit_sum_of_exclude_wg_commission = $debit_summary_details->sum('exclude_wg_commission');
                $debit_sum_of_exclude_wg_commission_vat = $debit_summary_details->sum('exclude_vat');
                $debit_sum_of_vat = $debit_summary_details->sum('vat');
                $debit_sum_of_wg_commission = $debit_summary_details->sum('wg_commission');
                $debit_sum_of_vat_commission = $debit_summary_details->sum('vat_commission');
                $debit_payable_to_store = $debit_summary_details->sum('payable_to_store');

                return [
                        'financial_transactions' => $financial_transactions,
                        'orders' => $orders,
                        'store' => $store,
                        'summary' => [
                                'sum_of_sales_price'=> ['title' => __t('titles.store.total_sales'), 'amount' => ['credit' => $credit_sum_of_sales_price, 'debit' => $debit_sum_of_sales_price]],
                                'sum_of_wg_commission'=> ['title' => __t('titles.store.wg_commission'), 'amount' => ['credit' => $credit_sum_of_wg_commission, 'debit' => $debit_sum_of_wg_commission]],
                                'sum_of_exclude_wg_commission'=> ['title' => __t('titles.store.total_sales_exclude_wg_comsn'), 'amount' => ['credit' => $credit_sum_of_exclude_wg_commission, 'debit' => $debit_sum_of_exclude_wg_commission]],
                                'sum_of_vat'=> ['title' => __t('titles.store.vat'), 'amount' => ['credit' => $credit_sum_of_vat, 'debit' => $debit_sum_of_vat]],
                                'sum_of_exclude_wg_commission_vat'=> ['title' => __t('titles.store.total_sales_exclude_wg_comsn_vat'), 'amount' => ['credit' => $credit_sum_of_exclude_wg_commission_vat, 'debit' => $debit_sum_of_exclude_wg_commission_vat]],
                                'sum_of_vat_commission'=> ['title' => __t('titles.store.total_sales_vat_comsn'), 'amount' => ['credit' => $credit_sum_of_vat_commission, 'debit' => $debit_sum_of_vat_commission]],
                                'payable_to_store'=> ['title' => __t('titles.store.total_sales_to_store'), 'amount' => ['credit' => $credit_payable_to_store, 'debit' => $debit_payable_to_store]],
                                ]
                ];
        }
}
