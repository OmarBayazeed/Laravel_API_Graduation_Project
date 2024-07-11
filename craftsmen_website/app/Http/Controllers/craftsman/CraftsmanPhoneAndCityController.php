<?php

namespace App\Http\Controllers\craftsman;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use App\Models\Craftsman;
use App\Models\Phone;
use Illuminate\Support\Facades\Validator;

class CraftsmanPhoneAndCityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_city(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $validator = Validator::make($request->all(), [
            'city' => 'required|array',
        ],[
            'city.required' => 'يجب أن تُرسل المدينة/المدن التي تعمل بها',
            'city.array' => 'يجب أن تُرسل المدن على هيئة مصفوفة',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors(), 'status' => false],401);
        }
        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message' => 'the craftsman not found', 'status' => false],404);
        }
        $exist = City::where('city',$request->city)->where('craftsman_id',$craftsman_id)->first();
        if ($exist) {
            return response()->json(['message' => 'لقد إخترت هذه المدينة بالفعل.', 'status' => false],401);
        }
        foreach ($request->city as $rcity) {
            $city = new City();
            $city->city = $rcity;
            $city->craftsman_id = $craftsman_id;
            $city->save();
        }

        if ($city) {
            return response()->json(['message' => 'تم إضافة المدينة بنجاح' , 'status' => true ],201);
        }
        else
        {
            return response()->json(['message' => 'لم نستطع إضافة المدينة', 'status' => false],401);
        }
    }


    public function update_city(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $exist = City::select()->where('craftsman_id',$craftsman_id)->get();
        if ($exist) {
            foreach ($exist as $ex) {
                $ex->delete();
            }
        }
        $validator = Validator::make($request->all(), [
            'city' => 'required|array',
        ],[
            'city.required' => 'يجب أن تُرسل المدينة/المدن التي تعمل بها',
            'city.array' => 'يجب أن تُرسل المدن على هيئة مصفوفة',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors(), 'status' => false],401);
        }

        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message' => 'the craftsman not found', 'status' => false],404);
        }
        foreach ($request->city as $rcity) {
            $city = new City();
            $city->city = $rcity;
            $city->craftsman_id = $craftsman_id;
            $city->save();
        }
        if ($city) {
            return response()->json(['message' => 'تم تحديث المدن بنجاح' , 'status' => true ],201);
        }
        else
        {
            return response()->json(['message' => 'لم نستطع تحديث المدن', 'status' => false],401);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store_phone(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $validator = Validator::make($request->all(), [
            'phone' => 'required|array',
            'phone.*' => 'numeric|digits:11',
            'whatsapp' => 'nullable|array',
            'whatsapp.*' => 'numeric|digits:11',
        ],[
            'phone.required' => 'يجب أن تُرسل رقمك',
            'phone.array' => 'يجب أن تُرسل الأرقام على هيئة مصفوفة',
            'phone.*.numeric' => 'يجب أن يتكون الرقم من أرقام فقط',
            'phone.*.digits:11' => 'يجب أن يتكون الرقم من 11 رقم',
            'whatsapp.array' => 'يجب أن تُرسل الرقم على هيئة مصفوفة',
            'whatsapp.*.numeric' => 'يجب أن يتكون الرقم من أرقام فقط',
            'whatsapp.*.digits:11' => 'يجب أن يتكون الرقم من 11 رقم',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors(), 'status' => false],401);
        }
        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message' => 'the craftsman not found', 'status' => false],404);
        }
        foreach ($request->phone as $rphone) {
            $phone = new Phone();
            $phone->phone = $rphone;
            $phone->craftsman_id = $craftsman_id;
            $phone->save();
        }
        if ($request->whatsapp) {
            foreach ($request->whatsapp as $rwhats) {
                $phone = new Phone();
                $phone->phone = $rwhats;
                $phone->type = 'whatsapp';
                $phone->craftsman_id = $craftsman_id;
                $phone->save();
            }
        }
        if ($phone) {
            return response()->json(['message' => 'تم إضافة الرقم بنجاح', 'status' => true],201);
        }
        else
        {
            return response()->json(['message' => 'لم نستطع إضافة الرقم', 'status' => false],401);
        }
    }

    public function update_phone(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $validator = Validator::make($request->all(), [
            'phone' => 'nullable|array',
            'phone.*' => 'numeric|digits:11',
            'whatsapp' => 'nullable|array',
            'whatsapp.*' => 'numeric|digits:11',
        ],[
            'phone.array' => 'يجب أن تُرسل الأرقام على هيئة مصفوفة',
            'phone.*.numeric' => 'يجب أن يتكون الرقم من أرقام فقط',
            'phone.*.digits:11' => 'يجب أن يتكون الرقم من 11 رقم',
            'whatsapp.array' => 'يجب أن تُرسل الرقم على هيئة مصفوفة',
            'whatsapp.*.numeric' => 'يجب أن يتكون الرقم من أرقام فقط',
            'whatsapp.*.digits:11' => 'يجب أن يتكون الرقم من 11 رقم',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors(), 'status' => false],401);
        }
        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message' => 'the craftsman not found', 'status' => false],404);
        }
        $exist = Phone::select()->where('craftsman_id',$craftsman_id)->get();
        if ($exist) {
            foreach ($exist as $ex) {
                $ex->delete();
            }
        }
        if ($request->phone){
            foreach ($request->phone as $rphone) {
                $phone = new Phone();
                $phone->phone = $rphone;
                $phone->craftsman_id = $craftsman_id;
                $phone->save();
            }
        }
        if ($request->whatsapp) {
            foreach ($request->whatsapp as $rwhats) {
                $phone = new Phone();
                $phone->phone = $rwhats;
                $phone->type = 'whatsapp';
                $phone->craftsman_id = $craftsman_id;
                $phone->save();
            }
        }
        if ($phone) {
            return response()->json(['message' => 'تم تحديث أرقامك بنجاح', 'status' => true],201);
        }
        else{
            return response()->json(['message' => 'لم نستطع تحديث أرقامك', 'status' => false],400);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
