<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientNotification;
use App\Models\Craftsman;
use App\Models\CraftsmanNotification;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function get_client_notifications(Request $request)
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
        $notifications =ClientNotification::select()->orderBy('id', 'desc')->where('client_id',$client_id)->paginate($pagination);
        if ($notifications->count() > 0) {
            return response()->json(['data' =>$notifications,'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'لا يوجد إشعارات','status' => false]);
        }
    }

    public function get_craftsman_notifications(Request $request)
    {
        $craftsman_id = $request->craftsman_id;
        if (!$craftsman_id) {
            return response()->json(['message' => 'you should give me the id of the craftsman in parameter(craftsman_id)','status' => false],404);
        }
        $pagination = $request->pagination;
        if (!$pagination) {
            return response()->json(['message' => 'you should give me the value of the pagination in parameter(pagination)','status' => false],404);
        }
        $craftsman = Craftsman::find($craftsman_id);
        if (!$craftsman) {
            return response()->json(['message'=>'craftsman not found','status'=>false,],404);
        }
        $notifications =CraftsmanNotification::select()->orderBy('id', 'desc')->where('craftsman_id',$craftsman_id)->paginate($pagination);
        if ($notifications->count() > 0) {
            return response()->json(['data' =>$notifications,'status' => true],200);
        }
        else
        {
            return response()->json(['message' => 'لا يوجد إشعارات','status' => false]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
