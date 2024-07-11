<?php

namespace App\Http\Controllers;

use App\Models\Craftsman;
use App\Models\SearchImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SearchImagesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function get_search_images(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }
        $images =SearchImages::select('id','image')->where('craftsman_id',$craftsman_id)->get();
        if ($images) {
            return response()->json(['data' =>$images,'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'لا يوجد صور لعرضها في البحث','status' => false]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function add_search_images(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }
        $validator = Validator::make($request->all(), [
            'image' => 'required|array',
            'image.*' => 'mimes:png,jpg,jpeg|max:50120',
        ],[
            'image.required' => 'يجب أن تُرسل صور',
            'image.array' => 'يجب أن تُرسل الصورة على هيئة مصفوفة',
            'image.*.mimes:png,jpg,jpeg' => 'الإمتداد يجب أن يكون png أو jpg أو jpeg',
            'image.*.max:50120' => 'يجب أن يكون حجم الصورة أقل من 50 ميجا',
        ]);
        if ($validator->fails()) {
            return response()->json([$validator->errors(),'status'=>false],401);
        }
        foreach ($request->file('image') as $img) {
            $search_image = new SearchImages();
            $imageName = 'search_images/'.Str::random().'.'.$img->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images/', $img, $imageName);
            $search_image->image = $imageName;
            $search_image->craftsman_id = $craftsman_id;
            $search_image->save();
        }
        if ($search_image) {
            return response()->json([
                'message' => 'تم إضاقة الصور بنجاح',
                'status' => true,
            ],201);
        }
        else
        {
            return response()->json(['message' => 'لم نستطع إضافة الصورة', 'status' => false],401);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update_search_images(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }
        $validator = Validator::make($request->all(), [
            'image' => 'nullable|array',
            'image.*' => 'mimes:png,jpg,jpeg|max:50120',
        ],[
            'image.array' => 'يجب أن تُرسل الصورة على هيئة مصفوفة',
            'image.*.mimes:png,jpg,jpeg' => 'الإمتداد يجب أن يكون png أو jpg أو jpeg',
            'image.*.max:50120' => 'يجب أن يكون حجم الصورة أقل من 50 ميجا',
        ]);
        if ($validator->fails()) {
            return response()->json([$validator->errors(),'status'=>false],401);
        }
        $search_images = SearchImages::select('image')->where('craftsman_id',$craftsman_id);
        if ($search_images) {
            foreach ($search_images as $search_image) {
                if ($search_image->image) {
                    $exist = Storage::disk('public')->exists('images/'. $search_image->image);
                    if ($exist) {
                        $exist = Storage::disk('public')->delete('images/'. $search_image->image);
                    }
                }
            }
            $search_images->delete();
        }else {
            return response()->json(['message' => 'craftsman does not have search images', 'status' => false],400);
        }
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $img) {
                $search_image = new SearchImages();
                $imageName = 'search_images/'.Str::random().'.'.$img->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('images/', $img, $imageName);
                $search_image->image = $imageName;
                $search_image->craftsman_id = $craftsman_id;
                $search_image->save();
            }
            if ($search_image) {
                return response()->json([
                    'message' => 'تم تعديل الصور بنجاح',
                    'status' => true,
                ],200);
            }
        }
        else
        {
            return response()->json(['message' => 'تم تعديل الصور بنجاح', 'status' => true],200);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete_search_image(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $image_id = $request->image_id;
        if (!$image_id) {
            return response()->json(['message' => 'you should give me the id of the image in parameter(image_id)','status' => false],404);
        }

        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }
        $search_image = SearchImages::where('id',$image_id)->where('craftsman_id',$craftsman_id)->first();
        if ($search_image) {
            $exist = Storage::disk('public')->exists('images/'. $search_image->image);
            if ($exist) {
                $exist = Storage::disk('public')->delete('images/'. $search_image->image);
            }
            $search_image->delete();
            if ($search_image) {
                return response()->json(['message' => 'تم ازالة الصورة بنجاح','status' => true],200);
            }
            else {
                return response()->json(['message' => 'لم نستطع إزالة الصورة','status' => false],400);
            }
        }
        else{
            return response()->json(['message' => 'there is no image with this id or the craftsman does not have search images','status' => false],400);
        }
    }
}
