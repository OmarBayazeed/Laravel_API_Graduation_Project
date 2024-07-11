<?php

namespace App\Http\Controllers\craftsman;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Craft;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Craftsman;
use App\Models\CraftsmanDoneJobs;
use App\Models\CraftsmanDoneJobsimage;
use App\Models\CraftsmanDoneJobsRating;
use App\Models\CraftsmanJob;
use App\Models\CraftsmanJobFinished;
use App\Models\JobsOfferReply;
use App\Models\Phone;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CraftsmanAuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('assign.guard:craftsman', ['except' => ['login', 'register']]);
        Config::set('auth.defaults.guard','craftsman');
        Config::set('auth.defaults.passwords','craftsmen');
    }
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ],[
            'email.required' => 'البريد الإلكتروني غير موجود',
            'email.email' => 'البريد الإلكتروني يجب أن يكون صالح للإستخدام',
            'password.required' => 'كلمة السر غير موجودة',
            'password.string' => 'كلمة السر يجب أن تكون نَص',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors(), 'status' => false], 422);
        }
        $craftsman = Craftsman::where('email', $request->email)->first();
        if ($craftsman) {
            $hashed = $craftsman->password;
            $normalPass = $request->password;
            if(Hash::check($normalPass,$hashed)) {
                $myTTL = 300000;
                JWTAuth::factory()->setTTL($myTTL);
                $craftName = Craft::select('name')->where('id' , $craftsman->craft_id)->first();
                if (!$craftName) {
                    $craftName = null;
                }
                $cities = City::select('city')->where('craftsman_id' , $craftsman->id)->get();
                if (!$cities) {
                    $cities = null;
                }
                $phones = Phone::select('phone')->where('craftsman_id' , $craftsman->id)->where('type','contact')->get();
                if (!$phones) {
                    $phones = null;
                }
                $whatsapp = Phone::select('phone')->where('craftsman_id' , $craftsman->id)->where('type','whatsapp')->get();
                if (!$whatsapp) {
                    $whatsapp = null;
                }
                $real_jobs = CraftsmanJob::select('id')->where('craftsman_id' , $craftsman->id)->get();
                $real_jobs_num = $real_jobs->count();
                if (!$real_jobs_num) {
                    $real_jobs_num = null;
                }
                $done_jobs = CraftsmanDoneJobs::select('id')->where('craftsman_id' , $craftsman->id)->get();
                $done_jobs_num = $done_jobs->count();
                if (!$done_jobs_num) {
                    $done_jobs_num = null;
                }
                // ratings
                if ($done_jobs) {
                    $Ratings =false;
                    foreach ($done_jobs as $done) {
                        $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                    }
                    if ($Ratings) {
                        foreach ($Ratings as $rating) {
                            $totalrates = array_sum(array_map("count", [$rating]));
                            for ($j=0; $j < $totalrates; $j++) {
                                $ratings_values[] = $rating[$j]['rating'];
                            }
                        }
                        if (isset($ratings_values)) {
                            $ratingValuesSum = array_sum($ratings_values);
                            $ratingValuesCount = array_sum(array_map("count", $Ratings));
                            $avgRating = round($ratingValuesSum/$ratingValuesCount);
                            if (!$avgRating) {
                                $avgRating = null;
                            }
                        }else {
                            $avgRating = null;
                            $ratingValuesCount = null;
                        }
                    }else {
                        $avgRating = null;
                        $ratingValuesCount = null;
                    }
                }else {
                    $avgRating = null;
                    $ratingValuesCount = null;
                }
                return response()->json([
                    'token' => JWTAuth::fromUser($craftsman),
                    "id" => $craftsman->id,
                    'name' => $craftsman->name,
                    'email' => $craftsman->email,
                    'address' => $craftsman->address,
                    'craftsman_status' => $craftsman->status,
                    'availability' => $craftsman->availability,
                    'description' => $craftsman->description,
                    'image' => $craftsman->image,
                    'craft' => $craftName,
                    'number_of_done_jobs' => $done_jobs_num,
                    'number_of_real_time jobs' => $real_jobs_num,
                    'rating' => $avgRating,
                    'number_of_ratings' => $ratingValuesCount,
                    'created_at' => $craftsman->created_at,
                    'cities' => $cities,
                    'phones' => $phones,
                    'whatsapp' => $whatsapp,
                    'status' => true,
                ]);
            } else {
                return response()->json([
                    'message' => 'كلمة السر خاطئة',
                    'status' => false,
                ],400);
            }
        }
        else {
            return response()->json([
                'message' => 'يجب أن تشترك أولا',
                'status' => false,
            ],404);
        }
    }
    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:craftsmen',
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->letters()->numbers()->mixedCase()->symbols(),
            ],
        ],[
            'name.required' => 'الاسم غير موجود',
            'name.string' => 'الاسم يجب أن يكون نَص',
            'name.between:2,100' => 'الاسم يجب أن يتكون من حرفين أو أكثر ويكون أقل من 100 حرف',
            'email.required' => 'البريد الإلكتروني غير موجود',
            'email.string' => 'البريد الالكتروني يجب أن يكون نَص',
            'email.email' => 'البريد الإلكتروني يجب أن يكون صالح للإستخدام',
            'email.max:100' => 'البريد الإلكتروني يجب أن لا يكون أكثر من 100 حرف',
            'email.unique:craftsmen' => 'هذا البريد الإلكتروني موجود بالفعل',
            'password.required' => 'كلمة السر غير موجودة',
            'password.string' => 'كلمة السر يجب أن تكون نَص',
            'password.confirmed' => 'يجب تأكيد كلمة السر',
        ]);
        if($validator->fails()){
            return response()->json(['message' => $validator->errors()->toJson(), 'status' => false], 400);
        }
        $craftsman = Craftsman::create(array_merge(
                    $validator->validated(),
                    ['password' => bcrypt($request->password)]
                ));
        return response()->json([
            'message' => 'تم الإشتراك بنجاح',
            'craftsman' => $craftsman,
            'status' => true,
        ], 201);
    }

    public function google(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string|max:255',
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100',
        ],[
            'name.required' => 'الاسم غير موجود',
            'name.string' => 'الاسم يجب أن يكون نَص',
            'name.between:2,100' => 'الاسم يجب أن يتكون من حرفين أو أكثر ويكون أقل من 100 حرف',
            'email.required' => 'البريد الإلكتروني غير موجود',
            'email.string' => 'البريد الالكتروني يجب أن يكون نَص',
            'email.email' => 'البريد الإلكتروني يجب أن يكون صالح للإستخدام',
            'email.max:100' => 'البريد الإلكتروني يجب أن لا يكون أكثر من 100 حرف',
            'user_id.required' => 'الرقم التعريفي غير موجود',
            'user_id.string' => 'الرقم التعريفي يجب أن تكون نَص',
            'user_id.max:255' => 'الرقم التعريفي يجب أن لا يكون أكثر من 255 حرف',
        ]);
        if($validator->fails()){
            return response()->json(["message" => $validator->errors(), 'status' => false], 400);
        }
        $craftsman = Craftsman::where('social_id',$request->user_id)->where('social_type','google')->first();
        if ($craftsman) {
            $myTTL = 300000;
            JWTAuth::factory()->setTTL($myTTL);
            $craftName = Craft::select('name')->where('id' , $craftsman->craft_id)->first();
            if (!$craftName) {
                $craftName = null;
            }
            $cities = City::select('city')->where('craftsman_id' , $craftsman->id)->get();
            if (!$cities) {
                $cities = null;
            }
            $phones = Phone::select('phone')->where('craftsman_id' , $craftsman->id)->where('type','contact')->get();
            if (!$phones) {
                $phones = null;
            }
            $whatsapp = Phone::select('phone')->where('craftsman_id' , $craftsman->id)->where('type','whatsapp')->get();
            if (!$whatsapp) {
                $whatsapp = null;
            }
            $real_jobs = CraftsmanJob::select('id')->where('craftsman_id' , $craftsman->id)->get();
            $real_jobs_num = $real_jobs->count();
            if (!$real_jobs_num) {
                $real_jobs_num = null;
            }
            $done_jobs = CraftsmanDoneJobs::select('id')->where('craftsman_id' , $craftsman->id)->get();
            $done_jobs_num = $done_jobs->count();
            if (!$done_jobs_num) {
                $done_jobs_num = null;
            }
            // ratings
            if ($done_jobs) {
                $Ratings =false;
                foreach ($done_jobs as $done) {
                    $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                }
                if ($Ratings) {
                    foreach ($Ratings as $rating) {
                        $totalrates = array_sum(array_map("count", [$rating]));
                        for ($j=0; $j < $totalrates; $j++) {
                            $ratings_values[] = $rating[$j]['rating'];
                        }
                    }
                    if (isset($ratings_values)) {
                        $ratingValuesSum = array_sum($ratings_values);
                        $ratingValuesCount = array_sum(array_map("count", $Ratings));
                        $avgRating = round($ratingValuesSum/$ratingValuesCount);
                        if (!$avgRating) {
                            $avgRating = null;
                        }
                    }else {
                        $avgRating = null;
                        $ratingValuesCount = null;
                    }
                }else {
                    $avgRating = null;
                    $ratingValuesCount = null;
                }
            }else {
                $avgRating = null;
                $ratingValuesCount = null;
            }
            return response()->json([
                'token' => JWTAuth::fromUser($craftsman),
                "id" => $craftsman->id,
                'name' => $craftsman->name,
                'email' => $craftsman->email,
                'address' => $craftsman->address,
                'craftsman_status' => $craftsman->status,
                'availability' => $craftsman->availability,
                'description' => $craftsman->description,
                'image' => $craftsman->image,
                'craft' => $craftName,
                'number_of_done_jobs' => $done_jobs_num,
                'number_of_real_time jobs' => $real_jobs_num,
                'rating' => $avgRating,
                'number_of_ratings' => $ratingValuesCount,
                'created_at' => $craftsman->created_at,
                'cities' => $cities,
                'phones' => $phones,
                'whatsapp' => $whatsapp,
                'status' => true,
            ]);
        }else {
            $craftsman = Craftsman::where('email',$request->email)->first();
            if ($craftsman) {
                return response()->json([
                    'message' => 'لديك حساب بالفعل! يجب أن تسجل دخول بإستخدام كلمة المرور الخاصة بك',
                    'status' => false,
                ], 400);
            }
            else {
                $craftsman = Craftsman::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'social_id' => $request->user_id,
                    'social_type' => 'google',
                ]);
                return response()->json([
                    'message' => 'تم الإشتراك بنجاح عن طريق جوجل',
                    'user' => $craftsman,
                    'status' => true,
                ], 201);
            }
        }
    }

    public function facebook(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string|max:255',
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100',
        ],[
            'name.required' => 'الاسم غير موجود',
            'name.string' => 'الاسم يجب أن يكون نَص',
            'name.between:2,100' => 'الاسم يجب أن يتكون من حرفين أو أكثر ويكون أقل من 100 حرف',
            'email.required' => 'البريد الإلكتروني غير موجود',
            'email.string' => 'البريد الالكتروني يجب أن يكون نَص',
            'email.email' => 'البريد الإلكتروني يجب أن يكون صالح للإستخدام',
            'email.max:100' => 'البريد الإلكتروني يجب أن لا يكون أكثر من 100 حرف',
            'email.unique:craftsmen' => 'هذا البريد الإلكتروني موجود بالفعل',
            'user_id.required' => 'الرقم التعريفي غير موجود',
            'user_id.string' => 'الرقم التعريفي يجب أن تكون نَص',
            'user_id.max:255' => 'الرقم التعريفي يجب أن لا يكون أكثر من 255 حرف',
        ]);
        if($validator->fails()){
            return response()->json(["message" => $validator->errors(), 'status' => false], 400);
        }
        $craftsman = Craftsman::where('social_id',$request->user_id)->where('social_type','facebook')->first();
        if ($craftsman) {
            $myTTL = 300000;
            JWTAuth::factory()->setTTL($myTTL);
            $craftName = Craft::select('name')->where('id' , $craftsman->craft_id)->first();
            if (!$craftName) {
                $craftName = null;
            }
            $cities = City::select('city')->where('craftsman_id' , $craftsman->id)->get();
            if (!$cities) {
                $cities = null;
            }
            $phones = Phone::select('phone')->where('craftsman_id' , $craftsman->id)->where('type','contact')->get();
            if (!$phones) {
                $phones = null;
            }
            $whatsapp = Phone::select('phone')->where('craftsman_id' , $craftsman->id)->where('type','whatsapp')->get();
            if (!$whatsapp) {
                $whatsapp = null;
            }
            $real_jobs = CraftsmanJob::select('id')->where('craftsman_id' , $craftsman->id)->get();
            $real_jobs_num = $real_jobs->count();
            if (!$real_jobs_num) {
                $real_jobs_num = null;
            }
            $done_jobs = CraftsmanDoneJobs::select('id')->where('craftsman_id' , $craftsman->id)->get();
            $done_jobs_num = $done_jobs->count();
            if (!$done_jobs_num) {
                $done_jobs_num = null;
            }
            // ratings
            if ($done_jobs) {
                $Ratings =false;
                foreach ($done_jobs as $done) {
                    $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
                }
                if ($Ratings) {
                    foreach ($Ratings as $rating) {
                        $totalrates = array_sum(array_map("count", [$rating]));
                        for ($j=0; $j < $totalrates; $j++) {
                            $ratings_values[] = $rating[$j]['rating'];
                        }
                    }
                    if (isset($ratings_values)) {
                        $ratingValuesSum = array_sum($ratings_values);
                        $ratingValuesCount = array_sum(array_map("count", $Ratings));
                        $avgRating = round($ratingValuesSum/$ratingValuesCount);
                        if (!$avgRating) {
                            $avgRating = null;
                        }
                    }else {
                        $avgRating = null;
                        $ratingValuesCount = null;
                    }
                }else {
                    $avgRating = null;
                    $ratingValuesCount = null;
                }
            }else {
                $avgRating = null;
                $ratingValuesCount = null;
            }
            return response()->json([
                'token' => JWTAuth::fromUser($craftsman),
                "id" => $craftsman->id,
                'name' => $craftsman->name,
                'email' => $craftsman->email,
                'address' => $craftsman->address,
                'craftsman_status' => $craftsman->status,
                'availability' => $craftsman->availability,
                'description' => $craftsman->description,
                'image' => $craftsman->image,
                'craft' => $craftName,
                'number_of_done_jobs' => $done_jobs_num,
                'number_of_real_time jobs' => $real_jobs_num,
                'rating' => $avgRating,
                'number_of_ratings' => $ratingValuesCount,
                'created_at' => $craftsman->created_at,
                'cities' => $cities,
                'phones' => $phones,
                'whatsapp' => $whatsapp,
                'status' => true,
            ]);
        }else {
            $craftsman = Craftsman::where('email',$request->email)->first();
            if ($craftsman) {
                return response()->json([
                    'message' => 'لديك حساب بالفعل! يجب أن تسجل دخول بإستخدام كلمة المرور الخاصة بك',
                    'status' => false,
                ], 400);
            }
            else {
                $craftsman = Craftsman::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'social_id' => $request->user_id,
                    'social_type' => 'facebook',
                ]);
                return response()->json([
                    'message' => 'تم الاشتراك بنجاح عن طريق فيسبوك',
                    'user' => $craftsman,
                    'status' => true,
                ], 201);
            }
        }
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {
        auth()->logout();
        return response()->json(['message' => 'تم تسجيل الخروج بنجاح','status' => true],200);
    }
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {
        return $this->createNewToken(auth('craftsman')->refresh());
    }
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile() {
        return response()->json([auth('craftsman')->user(),'status' => true]);
    }
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user(),
        ]);
    }



    public function completeInfo(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $validator = Validator::make($request->all(), [
            'address' => 'nullable',
            'description' => 'nullable|max:1000',
            'craft_id' => 'required',
            'image' => 'nullable|image|mimes:png,jpg,jpeg|max:50120',
            'phone' => 'required|array',
            'phone.*' => 'numeric|digits:11',
            'whatsapp' => 'nullable|array',
            'whatsapp.*' => 'numeric|digits:11',
            'city' => 'required|array',
        ],[
            'description.max:1000' => 'الوصف يجب أن يكون أقل من 1000 حرف',
            'craft_id.required' => 'يجب أن تختار صنعتك',
            'image.image' => 'الملف المُرسَل يجب أن يكون صورة',
            'image.mimes:png,jpg,jpeg' => 'الإمتداد يجب أن يكون png أو jpg أو jpeg',
            'image.max:50120' => 'يجب أن يكون حجم الصورة أقل من 50 ميجا',
            'phone.required' => 'يجب أن تُرسل رقمك',
            'phone.array' => 'يجب أن تُرسل الأرقام على هيئة مصفوفة',
            'phone.*.numeric' => 'يجب أن يتكون الرقم من أرقام فقط',
            'phone.*.digits:11' => 'يجب أن يتكون الرقم من 11 رقم',
            'whatsapp.array' => 'يجب أن تُرسل الرقم على هيئة مصفوفة',
            'whatsapp.*.numeric' => 'يجب أن يتكون الرقم من أرقام فقط',
            'whatsapp.*.digits:11' => 'يجب أن يتكون الرقم من 11 رقم',
            'city.required' => 'يجب أن تُرسل المدينة/المدن التي تعمل بها',
            'city.array' => 'يجب أن تُرسل المدن على هيئة مصفوفة',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors(),'status' => false],401);
        }

        $craftsman = Craftsman::find($craftsman_id);

        if (!$craftsman) {
            return response()->json(['message' => 'the craftsman not found','status' => false],404);
        }

        $craft = Craft::find($request->craft_id);

        if (!$craft) {
            return response()->json(['message' => 'the craft not found','status' => false],404);
        }

        if ($request->has('address'))
        {
            $craftsman->address = $request->address;
        }
        if ($request->has('description'))
        {
            $craftsman->description = $request->description;
        }
        if ($request->hasFile('image')) {
            if ($craftsman->image) {
                $exist = Storage::disk('public')->exists('images/'. $craftsman->image);
                if ($exist) {
                    $exist = Storage::disk('public')->delete('images/'. $craftsman->image);
                }
            }
            $imageName = 'craftsman/' . Str::random().'.'.$request->image->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images/', $request->image, $imageName);
            $craftsman->image = $imageName;
        }

        if ($request->has('phone'))
        {
            foreach ($request->phone as $rphone) {
                $phone = new Phone();
                $phone->phone = $rphone;
                $phone->craftsman_id = $craftsman_id;
                $phone->save();
            }
        }
        if ($request->has('whatsapp')) {
            foreach ($request->whatsapp as $rwhats) {
                $phone = new Phone();
                $phone->phone = $rwhats;
                $phone->type = 'whatsapp';
                $phone->craftsman_id = $craftsman_id;
                $phone->save();
            }
        }
        if ($request->has('city'))
        {
            $exist = City::where('city',$request->city)->where('craftsman_id',$craftsman_id)->first();
            if ($exist) {
                return response()->json(['message' => 'لقد إخترت هذه المدينة بالفعل.', 'status' => false],400);
            }
            foreach ($request->city as $rcity) {
                $city = new City();
                $city->city = $rcity;
                $city->craftsman_id = $craftsman_id;
                $city->save();
            }
        }
        $craftsman->craft_id = $request->craft_id;
        $craftsman->save();

        if ($craftsman) {
            return response()->json(['message' => 'تم إكمال البيانات','status' => true],201);
        }
        else
        {
            return response()->json(['message' => 'لم نستطع إكمال البيانات','status' => false],401);
        }


    }


    public function updateInfo(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $validator = Validator::make($request->all(), [
            'address' => 'nullable',
            'description' => 'nullable|max:1000',
            'image' => 'nullable|image|mimes:png,jpg,jpeg|max:50120',
            'password' => [
                'nullable',
                'string',
                'confirmed',
                Password::min(8)->letters()->numbers()->mixedCase()->symbols(),
            ],
        ],[
            'description.max:1000' => 'الوصف يجب أن يكون أقل من 1000 حرف',
            'image.image' => 'الملف المُرسَل يجب أن يكون صورة',
            'image.mimes:png,jpg,jpeg' => 'الإمتداد يجب أن يكون png أو jpg أو jpeg',
            'image.max:50120' => 'يجب أن يكون حجم الصورة أقل من 50 ميجا',
            'password.string' => 'كلمة السر يجب أن تكون نَص',
            'password.confirmed' => 'يجب تأكيد كلمة السر',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors(),'status' => false],400);
        }

        $craftsman = Craftsman::find($craftsman_id);

        if (!$craftsman) {
            return response()->json(['message' => 'the craftsman not found','status' => false],404);
        }

        if ($request->has('address'))
        {
            $craftsman->address = $request->address;
        }
        if ($request->has('description'))
        {
            $craftsman->description = $request->description;
        }
        if ($request->hasFile('image')) {
            if ($craftsman->image) {
                $exist = Storage::disk('public')->exists('images/'. $craftsman->image);
                if ($exist) {
                    $exist = Storage::disk('public')->delete('images/'. $craftsman->image);
                }
            }
            $imageName = 'craftsman/' . Str::random().'.'.$request->image->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images/', $request->image, $imageName);
            $craftsman->image = $imageName;
        }
        if ($request->has('password'))
        {
            $craftsman->password = bcrypt($request->password);
        }
        $craftsman->save();

        if ($craftsman) {
            if ($request->has('password'))
            {
                auth()->logout();
            }
            return response()->json(['message' => 'تم تحديث البيانات بنجاح','status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'لم نستطع تحديث البيانات','status' => false],400);
        }


    }


    public function get_user(Request $request) {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json([
            'message' => 'the craftsman not found',
            'status' => false,
        ],404);
        }
        // craft
        $craftName = Craft::select('name')->where('id' , $craftsman->craft_id)->first();
        if (!$craftName) {
            $craftName = null;
        }
        // cities
        $cities = City::select('city')->where('craftsman_id' , $craftsman->id)->get();
        if (!$cities) {
            $cities = null;
        }
        // phones
        $phones = Phone::select('phone')->where('craftsman_id' , $craftsman->id)->where('type','contact')->get();
        if (!$phones) {
            $phones = null;
        }
        $whatsapp = Phone::select('phone')->where('craftsman_id' , $craftsman->id)->where('type','whatsapp')->get();
        if (!$whatsapp) {
            $whatsapp = null;
        }
        // active jobs
        $real_jobs = CraftsmanJob::select('id')->where('craftsman_id' , $craftsman->id)->get();
        $real_jobs_num = $real_jobs->count();
        if (!$real_jobs_num) {
            $real_jobs_num = null;
        }
        // done jobs
        $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $craftsman->id)->where('status','finished')->get();
        $done_jobs_num = $done_jobs->count();
        if (!$done_jobs_num) {
            $done_jobs_num = null;
        }
        // ratings
        if ($done_jobs) {
            $Ratings =false;
            foreach ($done_jobs as $done) {
                $Ratings[] = CraftsmanDoneJobsRating::select('rating')->where('craftsmanDoneJob_id' , $done->id)->get();
            }
            if ($Ratings) {
                foreach ($Ratings as $rating) {
                    $totalrates = array_sum(array_map("count", [$rating]));
                    for ($j=0; $j < $totalrates; $j++) {
                        $ratings_values[] = $rating[$j]['rating'];
                    }
                }
                if (isset($ratings_values)) {
                    $ratingValuesSum = array_sum($ratings_values);
                    $ratingValuesCount = array_sum(array_map("count", $Ratings));
                    $avgRating = round($ratingValuesSum/$ratingValuesCount);
                    if (!$avgRating) {
                        $avgRating = null;
                    }
                }else {
                    $avgRating = null;
                    $ratingValuesCount = null;
                }
            }else {
                $avgRating = null;
                $ratingValuesCount = null;
            }
        }else {
            $avgRating = null;
            $ratingValuesCount = null;
        }


        return response()->json([
            'name' => $craftsman->name,
            'email' => $craftsman->email,
            'address' => $craftsman->address,
            'craftsman_status' => $craftsman->status,
            'availability' => $craftsman->availability,
            'description' => $craftsman->description,
            'image' => $craftsman->image,
            'craft' => $craftName,
            'number_of_done_jobs' => $done_jobs_num,
            'number_of_real_time_jobs' => $real_jobs_num,
            'rating' => $avgRating,
            'number_of_ratings' => $ratingValuesCount,
            'created_at' => $craftsman->created_at,
            'cities' => $cities,
            'phones' => $phones,
            'whatsapp' => $whatsapp,
            'status' => true,
        ]);
    }

    public function delete_account(Request $request) {
        auth()->logout();
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json([
            'message' => 'the craftsman not found',
            'status' => false,
        ],404);
        }
        $craftsman_cities = City::where('craftsman_id',$craftsman_id);
        if ($craftsman_cities) {
            $craftsman_cities->delete();
        }
        $phones = Phone::where('craftsman_id' , $craftsman->id);
        if ($phones) {
            $phones->delete();
        }
        $FinishActiveJob = CraftsmanJobFinished::where('craftsman_id',$craftsman_id);
        if ($FinishActiveJob) {
            $FinishActiveJob->delete();
        }
        $active_jobs = CraftsmanJob::where('craftsman_id' , $craftsman->id);
        if ($active_jobs) {
            $active_job_image = $active_jobs->image;
            if ($active_job_image) {
                $exist = Storage::disk('public')->exists('images/'. $active_job_image->image);
                if ($exist) {
                    $exist = Storage::disk('public')->delete('images/'. $active_job_image->image);
                }
            }
            $active_jobs->delete();
        }
        $done_jobs = CraftsmanDoneJobs::where('craftsman_id' , $craftsman->id);
        if ($done_jobs) {
            $Ratings =false;
            foreach ($done_jobs as $done) {
                $done_job_images = CraftsmanDoneJobsimage::where('craftsmanDoneJob_id',$done->id);
                foreach ($done_job_images as $done_job_image) {
                    if ($done_job_image->image) {
                        $exist = Storage::disk('public')->exists('images/'. $done_job_image->image);
                        if ($exist) {
                            $exist = Storage::disk('public')->delete('images/'. $done_job_image->image);
                        }
                        $done_job_image->delete();
                    }
                }
                $Ratings[] = CraftsmanDoneJobsRating::where('craftsmanDoneJob_id' , $done->id);
            }
            if ($Ratings) {
                $Ratings->delete();
                $done_jobs->delete();
            }
        }

        $oldReply = JobsOfferReply::where('craftsman_id',$craftsman_id);
        if ($oldReply) {
            $oldReply->delete();
        }
        $craftsman->delete();
        return response()->json(['message' => 'تم إزالة الحساب بنجاح','status' => true],200);
    }

}
