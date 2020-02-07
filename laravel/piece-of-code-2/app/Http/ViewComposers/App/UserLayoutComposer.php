<?php

namespace App\Http\ViewComposers\App;

use Illuminate\Contracts\View\View;

class UserLayoutComposer {

    public function compose(View $view) {

        $view->with([
            'items' => [
                'app.orders.index' => __t('labels.order_history'),
                'app.account.index' => __t('labels.my_account'),
                'app.account.change-pwd.form' => __t('labels.change_pass'),
                'app.account.destroy.form' => __t('titles.account.destroy.form'),
                'app.auth.logout' => __t('labels.log_out')
            ]
        ]);
    }
}