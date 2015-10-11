<?php namespace App\Http\Controllers;

use Brand;
use ProductCategory;
use Product;
use Request;
use Validator;
use Mail;

class ProductsController extends Controller {
	
    public function getIndex() {
        return view('products/index', ['selected_brand' => null, 'selected_category' => null]);
    }

    public function postSearchRedirect() {
        return redirect('products/search/' . Request::input('brand_id') . '/' . Request::input('category_id'));
    }

    public function getSearchResults($brandAlias, $categoryAlias, $page = 1) {    
    
        $brandId = Brand::where('alias', $brandAlias)->first()->id;
        $categoryId = ProductCategory::where('alias_es', $categoryAlias)->first()->id;

        $itemsPerPage = 20;
        
        $items = Product::search($brandId, $categoryId, $itemsPerPage);
            
        $total = $items->total();
        $from = ($total > 0)
            ? ($items->currentPage() - 1) * $itemsPerPage + 1
            : 0;
        $to = $from + $itemsPerPage - 1;
        if ($to > $total) {
            $to = $total;
        }

        $data = array(
            'result_count' => array(                
                'total' => $total,
                'from' => $from,
                'to' => $to
            ),
            'results' => $items
        );
        return view('products/search_results', [
            'data' => $data, 
            'data_json' => $items->toJson(),
            'selected_brand' => $brandAlias, 
            'selected_category' => $categoryAlias
        ]);   
    }

    public function postSendQuery() {
        $result = null;
        $msg = null;
        $errors = [];

        $validator = Validator::make(Request::all(), [
            'name' => 'required|max:100',
            'email' => 'required|email',
            'tel' => 'required'
        ]);

        if ($validator->passes()) {
            $sendResult = Mail::send(
                'emails.product_query', 
                [
                    'name' => Request::input('name'), 
                    'email' => Request::input('email'),
                    'tel' => Request::input('tel'),
                    'comments' => Request::input('comments'),
                    'product_code' => Request::input('itemCod'),
                    'product_description' => Request::input('itemDescrip')
                ], 
                function($message) {
                    $message
                        ->from(Request::input('email'), Request::input('name'))
                        ->to('german.medaglia@gmail.com')
                        //->bcc('german.medaglia@gmail.com')
                        ->subject('Consulta desde la web');
                }
            );  
            $result = $sendResult;
            $msg = ($result) ? 'Gracias por contactarse con nosotros...' : 'Ha ocurrido un error.';
        } else {       
            $errors = $validator->errors();
            $result = false;
        }        
        return response()->json(['result' => $result, 'msg' => $msg, 'errors' => $errors]);
    }

}