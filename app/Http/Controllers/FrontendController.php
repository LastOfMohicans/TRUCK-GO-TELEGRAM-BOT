<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FrontendController extends Controller
{
    public function welcome()
    {
        return view('index');
    }

    public function sendform(Request $request)
    {
        $data = $request->toArray();


        $inn = $data['inn'] ?? "";
        DB::table('request_to_join')->insert([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'inn' => $inn,
        ]);

        return view('sendform');
    }
}
