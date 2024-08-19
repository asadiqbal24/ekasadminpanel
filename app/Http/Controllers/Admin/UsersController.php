<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UsersController extends Controller
{
    public function index()
    {
        return view('content.users.index');
        
    }

    public function UsersList(Request $request)
    {
        $columns = [
        1 => 'id',
        2 => 'username',
        3 => 'email',
        4 => 'datetime',
        ];

        $search = [];

        $totalData = User::count();

        $totalFiltered = $totalData;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        if (empty($request->input('search.value'))) {
        $users = User::offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();
        } else {
        $search = $request->input('search.value');

        $users = User::where(function($q) use ($search){
            $q->where('username', 'LIKE', "%{$search}%");
            $q->orWhere('email', 'LIKE', "%{$search}%");
            $q->orWhere('datetime', 'LIKE', "%{$search}%");

        })->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $totalFiltered = User::where(function($q) use ($search){
            $q->where('username', 'LIKE', "%{$search}%");
            $q->orWhere('email', 'LIKE', "%{$search}%");
            $q->orWhere('datetime', 'LIKE', "%{$search}%");

        })->count();
        }

        $data = [];

        if (!empty($users)) {
        // providing a dummy id instead of database ids
        $ids = $start;

        foreach ($users as $user) {
            $nestedData['id'] = $user->id;
            $nestedData['username'] = $user->username;
            $nestedData['email'] = $user->email;
            $nestedData['datetime'] = $user->datetime;

            $data[] = $nestedData;
        }
        }

        if ($data) {
        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => intval($totalFiltered),
            'code' => 200,
            'data' => $data,
        ]);
        } else {
        return response()->json([
            'message' => 'Internal Server Error',
            'code' => 500,
            'data' => [],
        ]);
        }
    }

    public function create()
    {
        $heading = 'Create a new user';
        $sub_heading = 'You can now create a new user';
        return view('content.users.add', compact('heading', 'sub_heading'));
    }
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'username' => 'required',
            'password' => 'required',
        ]);
        $data = $request->all();
        $data['password'] = Hash::make($request->password);
        User::create($data);
        return redirect()->back()->with('message', 'user created successfully');
    }
    public function delete($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->delete();
        }
        return view('content.users.index', compact('user'));
    }

    public function makeAdmin($id)
    {
        $user = User::find($id);
        if($user) {
            if($user->is_admin == 0){
                $var = 'admin';
                $user->is_admin = 1;
            }else{
                $var = 'not admin';
                $user->is_admin = 0;
            }
        }
        $user->save();
        return redirect()->back()->with('message', 'now user is ' . $var);
    }
    public function verifyUser($id)
    {
        $user = User::find($id);
        if($user) {
            $user->email_verified_at = time();
            $user->save();
        }
        $user->save();
        return redirect()->back()->with('message', 'now user is verified ');
    }
}
