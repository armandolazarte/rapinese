<?php namespace App\Http\Controllers;

class PriceListController extends Controller
{
    
    public function getIndex()
    {
        return view('price_list/index');
    }

    public function getDownload()
    {
        return response()->download(base_path() . '/files/lista-de-precios.xls');
    }

}
