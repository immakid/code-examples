
;(function(app, undefined) {

    'use strict';

    // package
    app.services = app.services || {};

    app.services.api = function(__, dispatcher)
    {
        var pub = {}, f = {}, _i = {}, self = this, $$ = {
            last_callback:  null,
            callbacks:      {},
            uniqId:         0,
        };

        f.construct = function()
        {

        };

        pub.call = function(method, url, params, callb, extAjaxParams)
        {
            !extAjaxParams && (extAjaxParams={});

            var ajaxOptions =
                f.par__ajax_call_options(
                    method, url, params, callb);

            ajaxOptions.url = '/api_1'+ ajaxOptions.url;
            ajaxOptions = _.extend(ajaxOptions, extAjaxParams);

            $.ajax(ajaxOptions);

            return _i;
        };

        pub.req = function(method, url, params, callb)
        {
            var ajaxOptions =
                f.par__ajax_call_options(
                    method, url, params, callb);

            ajaxOptions.url =  ajaxOptions.url;
            $.ajax(ajaxOptions);

            return _i;
        };

        f.cnt_response__resp_object = function(response)
        {
            try
            {
                var ret = response.parseJSON(response);
            }
            catch(error)
            {
                var ret = {};
            }

            return ret;
        };

        f.par__ajax_call_options = function(method, url, params, callb)
        {
            !params && (params={});
            !callb && (callb={});

            var ajaxOptions = {
                type: method
            };

            if( method == 'POST' )
            {
                ajaxOptions.data = params;
            }
            else
            {
                var par = [];
                $.each(params, function(key, val)
                {
                    par.push(key+ '='+ val);
                });
                url += '?'+par.join('&');
            }

            if (url[0] != '/')
            {
                url = '/' + url;
            }

            ajaxOptions.url = url;

//            $$.last_callback = 'call'+ ($$.uniqId++);
            ajaxOptions.success = function(resp)
            {
                if (typeof resp == 'string')
                {
                    ajaxOptions.error(resp);
                    return
                }

                if (resp.proxycnt)
                {
                    resp = resp.proxycnt;
                }

                if (resp.modelsUpdate)
                {
                    dispatcher.dispatch(
                        app.e.models__update, resp.modelsUpdate);
                }

                if(resp.ok)
                {
                    callb.done && callb.done(resp.product);
                } else {
                    callb.fail && callb.fail(resp);
                }
            };

            ajaxOptions.error = function(resp)
            {
                callb.fail && callb.fail(resp);
            };

            return ajaxOptions;
        };


        f.construct();

        _i = {
            f: f,
            call:      pub.call,
            req:       pub.req,
        };

        return _i;
    };


})(window.app = window.app || {});