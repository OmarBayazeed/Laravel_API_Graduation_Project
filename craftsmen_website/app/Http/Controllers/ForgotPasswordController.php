<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Craftsman;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class ForgotPasswordController extends Controller
{


    public function craftsman_send_email(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:100',
        ],[
            'email.required' => 'البريد الإلكتروني غير موجود',
            'email.string' => 'البريد الالكتروني يجب أن يكون نَص',
            'email.email' => 'البريد الإلكتروني يجب أن يكون صالح للإستخدام',
            'email.max:100' => 'البريد الإلكتروني يجب أن لا يكون أكثر من 100 حرف',
        ]);
        if($validator->fails()){
            return response()->json(['message' => $validator->errors(), 'status' => false], 400);
        }

        $craftsman = Craftsman::where('email',$request->email)->first();
        if ($craftsman) {
            $token = Str::random(30);
            $data['token'] = $token;
            $data['email'] = $request->email;
            $data['title'] = 'Password Reset';
            $data['body'] = 'استعمل هذا الكود في إعادة تعيين كلمة المرور.';

            Mail::send('forgetPasswordMail', ['data' => $data], function ($message) use ($data) {
                $message->to($data['email'])->subject($data['title']);
            });
            $emailVerification= PasswordReset::where('email',$request->email);
            if ($emailVerification) {
                $emailVerification->delete();
            }
            $password_reset = new PasswordReset();
            $password_reset->email = $request->email;
            $password_reset->token = $token;
            $password_reset->save();
            if ($password_reset) {
                return response()->json(['message' => 'تفقد بريدك الالكتروني من فضلك', 'status' => true], 200);
            }else {
                return response()->json(['message' => 'حدثت مشكلة', 'status' => false], 400);
            }

        }else {
            return response()->json(['message' => 'هذا الحساب غير موجود', 'status' => false], 404);
        }
    }

    public function craftsman_reset_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->letters()->numbers()->mixedCase()->symbols(),
            ],
            'code' => 'required'
        ],[
            'password.required' => 'كلمة السر غير موجودة',
            'password.string' => 'كلمة السر يجب أن تكون نَص',
            'password.confirmed' => 'يجب تأكيد كلمة السر',
            'code.required' => 'لم تُرسل الكود الخاص بك',
        ]);
        if($validator->fails()){
            return response()->json(['message' => $validator->errors(), 'status' => false], 400);
        }

        $reset = PasswordReset::where('token',$request->code)->first();
        if ($reset) {
            $craftsman = Craftsman::where('email',$reset->email)->first();
            $craftsman->password = bcrypt($request->password);
            $craftsman->save();
            $reset->delete();
            return response()->json(['message' => 'تم تعديل كلمة السر بنجاح', 'status' => true], 200);
        }else {
            return response()->json(['message' => 'الكود الخاص بك غير صحيح', 'status' => false], 400);
        }
    }


    public function client_send_email(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:100',
        ]);
        if($validator->fails()){
            return response()->json(['message' => $validator->errors(), 'status' => false], 400);
        }

        $client = Client::where('email',$request->email)->first();
        if ($client) {
            $token = Str::random(30);
            $data['token'] = $token;
            $data['email'] = $request->email;
            $data['title'] = 'Password Reset';
            $data['body'] = 'استعمل هذا الكود في إعادة تعيين كلمة المرور.';

            Mail::send('forgetPasswordMail', ['data' => $data], function ($message) use ($data) {
                $message->to($data['email'])->subject($data['title']);
            });
            $emailVerification= PasswordReset::where('email',$request->email);
            if ($emailVerification) {
                $emailVerification->delete();
            }
            $password_reset = new PasswordReset();
            $password_reset->email = $request->email;
            $password_reset->token = $token;
            $password_reset->save();
            if ($password_reset) {
                return response()->json(['message' => 'تفقد بريدك الالكتروني من فضلك', 'status' => true], 200);
            }else {
                return response()->json(['message' => 'حدثت مشكلة', 'status' => false], 400);
            }

        }else {
            return response()->json(['message' => 'هذا الحساب غير موجود', 'status' => false], 404);
        }
    }



    public function client_reset_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->letters()->numbers()->mixedCase()->symbols(),
            ],
            'code' => 'required'
        ],[
            'password.required' => 'كلمة السر غير موجودة',
            'password.string' => 'كلمة السر يجب أن تكون نَص',
            'password.confirmed' => 'يجب تأكيد كلمة السر',
            'code.required' => 'لم تُرسل الكود الخاص بك',
        ]);
        if($validator->fails()){
            return response()->json(['message' => $validator->errors(), 'status' => false], 400);
        }

        $reset = PasswordReset::where('token',$request->code)->first();
        if ($reset) {
            $client = Client::where('email',$reset->email)->first();
            $client->password = bcrypt($request->password);
            $client->save();
            $reset->delete();
            return response()->json(['message' => 'تم تعديل كلمة السر بنجاح', 'status' => true], 200);
        }else {
            return response()->json(['message' => 'الكود الخاص بك غير صحيح', 'status' => false], 400);
        }
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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
