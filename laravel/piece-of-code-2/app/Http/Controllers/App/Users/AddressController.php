<?php

namespace App\Http\Controllers\App\Users;

use App\Models\Address;
use App\Models\Country;
use App\Http\Controllers\FrontendController;
use App\Http\Requests\SubmitAddressFormRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AddressController extends FrontendController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('ajax', ['only' => ['create', 'edit']]);
    }
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        force_return_back();
        return view('app.users.account.addresses.create', [
            'countries' => Country::where('code', 'SE')->get(),
            'types' => config('cms.addresses.types'),
        ]);
    }

    /**
     * @param Address $address
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Address $address)
    {
        force_return_back();
        return view('app.users.account.addresses.edit', [
            'item' => $address,
            'countries' => Country::where('code', 'SE')->get(),
        ]);
    }

    /**
     * @param SubmitAddressFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(SubmitAddressFormRequest $request)
    {
        $address = new Address($request->all());
        $address->saveRelationsFromRequest($request);

        if (!$address->save()) {
            flash()->error(__t('messages.error.saving'));
            return redirect()->intended(route_region('app.account.index'));
        }

        foreach ($request->input('types', []) as $type) {
            $this->userRepository->current()->addresses()->attach($address, ['type' => $type]);
        }

        flash()->success(__t('messages.success.saved', ['object' => __t('messages.objects.address')]));
        return redirect()->intended(route_region('app.account.index'));
    }

    /**
     * @param SubmitAddressFormRequest $request
     * @param Address $address
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(SubmitAddressFormRequest $request, Address $address)
    {
        if ($address->saveRelationsFromRequest($request)->update($request->all())) {
            flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.address')]));
        } else {
            flash()->error(__t('messages.error.saving'));
        }

        return redirect()->intended(route_region('app.account.index'));
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        try {
            Address::findOrFail($id)->delete();
            flash()->success(__t('messages.success.deleted', ['object' => __t('messages.objects.address')]));
        } catch (ModelNotFoundException $e) {
            flash()->error(__t('messages.error.invalid_request'));
        }

        return redirect()->intended(route_region('app.account.index'));
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function guestCreate()
    {
        force_return_back();
        return view('app.cart._partials.new-guest_create', [
            'countries' => Country::where('code', 'SE')->get(),
            'types' => config('cms.addresses.types'),
        ]);
    }


    /**
     * @param SubmitAddressFormRequest $request
     * @return mixed
     */
    public function guestStore(SubmitAddressFormRequest $request)
    {
        try {
            $issetSession = session()->get('guest_address');
            $data = $request->all();
            $country = Country::where('id', $data['country_id'])->first();
            $data['country'] = __t(sprintf("countries.%s", strtolower($country->code)));
            session()->put('guest_address', $data);
            $data['messages'] = __t('messages.success.saved', ['object' => __t('messages.objects.address')]);
            if($issetSession){
                $data['messages'] =  __t('messages.success.updated', ['object' => __t('messages.objects.address')]);
            }

            return $data;
        }catch (\Exception $e){
            return response()->json($e);
        }
    }

}
