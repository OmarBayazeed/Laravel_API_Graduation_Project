<?php

namespace App\Http\Controllers;

use App\Models\Craft;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CraftsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $crafts =Craft::select('id', 'name', 'image')->get();

        if ($crafts) {
            return response()->json(['data' => $crafts,'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'there is no crafts','status' => false],404);
        }
    }


    public function get_one_craft(Request $request)
    {
        $craft_id = $request->craft_id;
        if (!$craft_id) {
            return response()->json(['message' => 'you should give me the id of the craft in parameter(craft_id)','status' => false],404);
        }
        $crafts =Craft::select('id', 'name', 'image')->where('id',$craft_id)->first();

        if ($crafts) {
            return response()->json(['data' => $crafts,'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'there is no crafts','status' => false],404);
        }
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'image' => 'nullable|image|mimes:png,jpg,jpeg|max:50120',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors(), 'status' => false],401);
        }
        $craft = new Craft();
        $craft->name = $request->name;
        if ($request->hasFile('image')) {
            if ($craft->image) {
                $exist = Storage::disk('public')->exists('images/'. $craft->image);
                if ($exist) {
                    $exist = Storage::disk('public')->delete('images/'. $craft->image);
                }
            }
            $imageName = 'crafts/' . Str::random().'.'.$request->image->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images/', $request->image, $imageName);
            $craft->image = $imageName;
        }
        $craft->save();

        if ($craft) {
            return response()->json(['message' => 'craft stored successfully', 'status' => true],201);
        }
        else
        {
            return response()->json(['message' => 'can not store this craft', 'status' => false],401);
        }
    }




    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $craft_id = $request->craft_id;
        if (!$craft_id) {
            return response()->json(['message' => 'you should give me the id of the craft in parameter(craft_id)','status' => false],404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'nullable',
            'image' => 'nullable|image|mimes:png,jpg,jpeg|max:50120',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors(), 'status' => false],401);
        }
        $craft = Craft::find($craft_id);
        if ($craft) {
            if ($request->has('name')) {
                $craft->name = $request->name;
            }
            if ($request->hasFile('image')) {
                if ($craft->image) {
                    $exist = Storage::disk('public')->exists('images/'. $craft->image);
                    if ($exist) {
                        $exist = Storage::disk('public')->delete('images/'. $craft->image);
                    }
                }
                $imageName = 'crafts/' . Str::random().'.'.$request->image->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/', $request->image, $imageName);
                $craft->image = $imageName;
            }
            $craft->save();

            if ($craft) {
                return response()->json(['message' => 'craft updated successfully', 'status' => true],201);
            }
            else
            {
                return response()->json(['message' => 'can not update this craft', 'status' => false],400);
            }
        }else {
            return response()->json(['message' => 'there is no craft with this id', 'status' => false],400);
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
