<?php

namespace App\Http\Controllers\Backend\Stores;

use App\Http\Controllers\BackendController;
use App\Http\Requests\Stores\UploadPriceFileFormRequest;
use App\Models\Stores\Store;

class PriceFileController extends BackendController {

    /**
     * @param Store $store
     * @param UploadPriceFileFormRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Store $store, UploadPriceFileFormRequest $request) {

        $priceFile = $store->priceFile;
        if ($priceFile && !$priceFile->isRemote && $priceFile->hasMandatoryMappings) {

            $upload = $request->file('media.file');
            $data = file_get_contents($upload->getPathname());

            if (file_put_contents($priceFile->localFileName, $data)) {

                $priceFile->enable();
                flash()->success(__t('messages.success.updated', ['object' => __t('messages.objects.price_file')]));
            }

        }

        return redirect()->back();
    }
}
