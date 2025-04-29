<?php


namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Client;
use App\Models\ClientsRating;
use App\Models\Craftsman;
use App\Models\Favorite;
use App\Models\JobsOffer;
use App\Models\JobsOfferImage;
use App\Models\JobsOfferReply;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClientAuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('assign.guard:client', ['except' => ['login', 'register']]);
        Config::set('auth.defaults.guard','client');
        Config::set('auth.defaults.passwords','clients');
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
            'mobile_token'  => 'required',
        ],[
            'email.required' => 'البريد الإلكتروني غير موجود',
            'email.email' => 'البريد الإلكتروني يجب أن يكون صالح للإستخدام',
            'password.required' => 'كلمة السر غير موجودة',
            'password.string' => 'كلمة السر يجب أن تكون نَص',
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), 'status' => false], 400);
        }
        $client = Client::where('email', $request->email)->first();
        if ($client) {
            $hashed = $client->password ;
            $normalPass = $request->password;
            if(Hash::check($normalPass,$hashed)) {
                $client->mobile_token = $request->mobile_token;
                $client->save();
                $myTTL = 300000;
                JWTAuth::factory()->setTTL($myTTL);

                return [
                    'token' => JWTAuth::fromUser($client),
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'address' => $client->address,
                    'phone' => $client->phone,
                    'image' => $client->image,
                    'created_at' => $client->created_at,
                    'status' => true,
                ];
            } else {
                return response()->json([
                    'message' => 'كلمة السر خاطئة',
                    'status' =>false,
                ],422);
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
            'email' => 'required|string|email|max:100|unique:clients',
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
            'email.unique:clients' => 'هذا البريد الإلكتروني موجود بالفعل',
            'password.required' => 'كلمة السر غير موجودة',
            'password.string' => 'كلمة السر يجب أن تكون نَص',
            'password.confirmed' => 'يجب تأكيد كلمة السر',
        ]);
        if($validator->fails()){
            return response()->json(["message" => $validator->errors(), 'status' => false], 400);
        }
        $user = Client::create(array_merge(
                    $validator->validated(),
                    ['password' => bcrypt($request->password)]
                ));
        return response()->json([
            'message' => 'تم الإشتراك بنجاح',
            'user' => $user,
            'status' => true,
        ], 201);
    }

    public function google(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string|max:255',
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100',
            'mobile_token'  => 'required',
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
        $user = Client::where('social_id',$request->user_id)->where('social_type','google')->first();
        if ($user) {
            $user->mobile_token = $request->mobile_token;
            $user->save();
            $myTTL = 300000;
            JWTAuth::factory()->setTTL($myTTL);

            return response()->json([
                'token' => JWTAuth::fromUser($user),
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'address' => $user->address,
                'phone' => $user->phone,
                'image' => $user->image,
                'created_at' => $user->created_at,
                'status' => true,
            ]);
        }else {
            $client = Client::where('email',$request->email)->first();
            if ($client) {
                return response()->json([
                    'message' => 'لديك حساب بالفعل! يجب أن تسجل دخول بإستخدام كلمة المرور الخاصة بك',
                    'status' => false,
                ], 400);
            }
            else {
                $user = Client::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'social_id' => $request->user_id,
                    'social_type' => 'google',
                ]);
                return response()->json([
                    'message' => 'تم الإشتراك بنجاح عن طريق جوجل',
                    'user' => $user,
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
            'mobile_token'  => 'required',
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
        $user = Client::where('social_id',$request->user_id)->where('social_type','facebook')->first();
        if ($user) {
            $user->mobile_token = $request->mobile_token;
            $user->save();
            $myTTL = 300000;
            JWTAuth::factory()->setTTL($myTTL);

            return [
                'token' => JWTAuth::fromUser($user),
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'address' => $user->address,
                'phone' => $user->phone,
                'image' => $user->image,
                'created_at' => $user->created_at,
                'status' => true,
            ];
        }else {
            $client = Client::where('email',$request->email)->first();
            if ($client) {
                return response()->json([
                    'message' => 'لديك حساب بالفعل! يجب أن تسجل دخول بإستخدام كلمة المرور الخاصة بك',
                    'status' => false,
                ], 400);
            }
            else {
                $user = Client::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'social_id' => $request->user_id,
                    'social_type' => 'facebook',
                ]);
                return response()->json([
                    'message' => 'تم الاشتراك بنجاح عن طريق فيسبوك',
                    'user' => $user,
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
        return response()->json(['message' => 'تم تسجيل الخروج بنجاح', 'status' => true],200);
    }
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {
        return $this->createNewToken(auth()->refresh());
    }
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile() {
        return response()->json([auth()->user(),'status' => true],200);
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
            'user' => auth()->user()
        ]);
    }




    public function updateInfo(Request $request)
    {
        $client_id = $request->client_id;
        if (!$client_id) {
            return response()->json(['message' => 'you should give me the id of the client in parameter(client_id)','status' => false],404);
        }
        $validator = Validator::make($request->all(), [
            'address' => 'nullable',
            'phone' => 'nullable|numeric|digits:11',
            'image' => 'nullable|image|mimes:png,jpg,jpeg|max:50120',
            'password' => [
                'nullable',
                'string',
                'confirmed',
                Password::min(8)->letters()->numbers()->mixedCase()->symbols(),
            ],
        ],[
            'image.image' => 'الملف المُرسَل يجب أن يكون صورة',
            'image.mimes:png,jpg,jpeg' => 'الإمتداد يجب أن يكون png أو jpg أو jpeg',
            'image.max:50120' => 'يجب أن يكون حجم الصورة أقل من 50 ميجا',
            'password.string' => 'كلمة السر يجب أن تكون نَص',
            'password.confirmed' => 'يجب تأكيد كلمة السر',
            'phone.numeric' => 'يجب أن يتكون الرقم من أرقام فقط',
            'phone.digits:11' => 'يجب أن يتكون الرقم من 11 رقم',
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), 'status' => false],401);
        }

        $client = Client::find($client_id);

        if (!$client) {
            return response()->json(['message' => 'the client not found', 'status' => false],404);
        }

        if ($request->has('address'))
        {
            $client->address = $request->address;
        }
        if ($request->has('phone'))
        {
            $client->phone = $request->phone;
        }
        if ($request->hasFile('image')) {
            if ($client->image) {
                $exist = Storage::disk('public')->exists('images/'. $client->image);
                if ($exist) {
                    $exist = Storage::disk('public')->delete('images/'. $client->image);
                }
            }
            $imageName = 'client/'.Str::random().'.'.$request->image->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images/', $request->image, $imageName);
            $client->image = $imageName;
        }
        if ($request->has('password'))
        {
            $client->password = bcrypt($request->password);
        }

        $client->save();

        if ($client) {
            if ($request->has('password'))
            {
                auth()->logout();
            }
            return response()->json(['message' => 'تم تحديث البيانات بنجاح', 'status' => true],200);
        }
        else
        {
            return response()->json(['message' =>'لم نستطع تحديث البيانات', 'status' => true],400);
        }


    }


    public function get_user(Request $request) {

        $client_id = $request->client_id;
        if (!$client_id) {
            return response()->json(['message' => 'you should give me the id of the client in parameter(client_id)','status' => false],404);
        }
        $client = Client::find($client_id);

        if (!$client) {
            return response()->json([
            'message' => 'the client not found',
            'status' => false,
        ],404);
        }
        // ratings
        $Ratings =false;
        if (ClientsRating::select('rating')->where('client_id' , $client_id)->get()->count() > 0) {
            $Ratings[] = ClientsRating::select('rating')->where('client_id' , $client_id)->get();
        }
        if ($Ratings) {
            foreach ($Ratings as $rating) {
                $totalrates = array_sum(array_map("count", [$rating]));
                for ($j=0; $j < $totalrates; $j++) {
                    $ratings_values[] = $rating[$j]['rating'];
                }
            }
            if ($ratings_values) {
                $ratingValuesSum = array_sum($ratings_values);
                $ratingValuesCount = array_sum(array_map("count", $Ratings));
                $avgRating = round($ratingValuesSum/$ratingValuesCount);
                if (!$avgRating) {
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
            'name' => $client->name,
            'email' => $client->email,
            'address' => $client->address,
            'phone' => $client->phone,
            'image' => $client->image,
            'created_at' => $client->created_at,
            'rating' => $avgRating,
            'number_of_ratings' => $ratingValuesCount,
            'status' => true,
        ],200);
    }



    public function get_all_ratings(Request $request)
    {
        $client_id = $request->client_id;
        if (!$client_id) {
            return response()->json(['message' => 'you should give me the id of the client in parameter(client_id)','status' => false],404);
        }
        $pagination = $request->pagination;
        if (!$pagination) {
            return response()->json(['message' => 'you should give me the value of the pagination in parameter(pagination)','status' => false],404);
        }
        $client = Client::find($client_id);
        if (!$client) {
            return response()->json(['message'=>'client not found','status'=>false,],404);
        }
        $Ratings =false;
        $Ratings = ClientsRating::select('rating','comment','created_at','craftsman_id')->orderBy('id', 'desc')->where('client_id' , $client_id)->paginate($pagination)->through(function($rt){
            $craftsman= Craftsman::find($rt->craftsman_id);
            if ($craftsman) {
                $rt['craftsman_name'] = $craftsman->name;
                $rt['craftsman_image'] = $craftsman->image;
            }
            else {
                $rt['craftsman_name'] = null;
                $rt['craftsman_image'] = null;
            }
            return $rt;
        });
        if ($Ratings->count() > 0) {
            return response()->json([
                'data' => $Ratings,
                'status' => true,
            ],200);
        }else {
            return response()->json([
                'message' => 'لا يوجد تقييمات بعد',
                'status' => false,
            ]);
        }
    }


    public function delete_account(Request $request) {
        auth()->logout();
        $client_id = $request->client_id;
        if (!$client_id) {
            return response()->json(['message' => 'you should give me the id of the client in parameter(client_id)','status' => false],404);
        }
        $client = Client::find($client_id);
        if (!$client) {
            return response()->json([
            'message' => 'the client not found',
            'status' => false,
        ],404);
        }
        $favorite = Favorite::where('client_id',$client_id);
        if ($favorite) {
            $favorite->delete();
        }
        $offer_jobs = JobsOffer::where('client_id' , $client->id);
        if ($offer_jobs) {
            foreach ($offer_jobs as $offer) {
                $offer_job_images = JobsOfferImage::where('Job_offer_id',$offer->id);
                foreach ($offer_job_images as $offer_job_image) {
                    if ($offer_job_image->image) {
                        $exist = Storage::disk('public')->exists('images/'. $offer_job_image->image);
                        if ($exist) {
                            $exist = Storage::disk('public')->delete('images/'. $offer_job_image->image);
                        }
                        $offer_job_image->delete();
                    }
                }
                $replies = JobsOfferReply::where('job_offer_id',$offer->id);
                if ($replies) {
                    $replies->delete();
                }
            }
        }
        $client->delete();
        return response()->json(['message' => 'تم إزالة حسابك بنجاح','status' => true],200);
    }

}

