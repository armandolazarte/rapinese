<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Validator;
use Mail;
use Lang;

use PriceListDownloadToken;

class PriceListController extends Controller
{
    
    public function getIndex()
    {
        return view('price_list/index');
    }

    public function postIndex(Request $request)
    {
        $rules = [
            'email' => 'required|email'
        ];        
        $validator = Validator::make($request->all(), $rules);
        if ($validator->passes()) {
            $token = new PriceListDownloadToken();
            $token->value = str_random(30);
            $token->fill($request->all());
            if ($token->save()) {

                $sendResult = Mail::send(
                    'emails.price_list_download_token', 
                    [
                        'token' => $token->value
                    ], 
                    function($message) use ($request) {
                        $message
                            ->from($request->input('email')/*, $request->input('name')*/)
                            ->to('german.medaglia@gmail.com' /*'rapinese@rapinese.com.ar'*/)
                            ->bcc('german.medaglia@gmail.com')
                            ->subject('Lista de precios');
                    }
                );  
                $result = $sendResult != false;
                $msg = ($result) 
                    ? trans('alerts.products.ask.success') 
                    : trans('alerts.products.ask.error');

                return redirect()
                    ->route('price-list-token-sent')
                    ->with(['token' => $token->value, 'token_sent' => true]);
            } else {

            }
        } else {
            return redirect()->back()->withInput()->withErrors($validator->messages());
        }
    }

    public function getTokenSent(Request $request)
    {
        if (!$request->session()->get('token_sent')) {
            return redirect()->route('price-list');
        }        
        return view('price_list/token_sent', ['token' => $request->session()->get('token')]);
    }

    public function getDownload($tokenVal)
    {
        $token = new PriceListDownloadToken();
        $valid = $token->isValid($tokenVal)->count() > 0;
        if (!$valid) {
            abort(404);
        }
        return response()->download(base_path() . '/files/lista-de-precios.xls');
    }

}
