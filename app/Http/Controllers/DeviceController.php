<?php

namespace App\Http\Controllers;

use Yajra\DataTables\Facades\Datatables;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Attendance;
use DB;

class DeviceController extends Controller
{
    // Menampilkan daftar device
    public function index(Request $request)
    {
        $data['lable'] = "Devices";
        $data['log'] = DB::table('devices')->select('id','no_sn','online')->orderBy('online', 'DESC')->get();
        return view('devices.index',$data);
    }

    public function DeviceLog(Request $request)
    {
        $sn = $request->query('sn');
        $query = DB::table('device_log');
        
        if ($sn) {
            $query->where('sn', $sn);
        }
        
        $log = $query->orderBy('id', 'desc')->get();
        $lable = "Device Handshake Log" . ($sn ? " for $sn" : "");
        return view('devices.log', compact('log', 'lable'));
    }
    
    public function FingerLog(Request $request)
    {
        $data['lable'] = "Finger Log";
        $data['log'] = DB::table('finger_log')->select('id','data','url')->orderBy('id','DESC')->get();
        return view('devices.log',$data);
    }
    public function Attendance() {
       //$attendances = Attendance::latest('timestamp')->orderBy('id','DESC')->paginate(15);
       $attendances = DB::table('attendances')->select('id','sn','table','stamp','employee_id','timestamp','status1','status2','status3','status4','status5')->orderBy('id','DESC')->paginate(15);

        return view('devices.attendance', compact('attendances'));
        
    }

    public function ApiDocs() {
        $tokens = DB::table('api_tokens')->select('id', 'name', 'is_active', 'expires_at', 'last_used_at', 'created_at')->get();
        return view('api-docs', compact('tokens'));
    }

    // // Menampilkan form tambah device
    // public function create()
    // {
    //     return view('devices.create');
    // }

    // // Menyimpan device baru ke database
    // public function store(Request $request)
    // {
    //     $device = new Device();
    //     $device->nama = $request->input('nama');
    //     $device->no_sn = $request->input('no_sn');
    //     $device->lokasi = $request->input('lokasi');
    //     $device->save();

    //     return redirect()->route('devices.index')->with('success', 'Device berhasil ditambahkan!');
    // }

    // // Menampilkan detail device
    // public function show($id)
    // {
    //     $device = Device::find($id);
    //     return view('devices.show', compact('device'));
    // }

    // // Menampilkan form edit device
    // public function edit($id)
    // {
    //     $device = Device::find($id);
    //     return view('devices.edit', compact('device'));
    // }

    // // Mengupdate device ke database
    // public function update(Request $request, $id)
    // {
    //     $device = Device::find($id);
    //     $device->nama = $request->input('nama');
    //     $device->no_sn = $request->input('no_sn');
    //     $device->lokasi = $request->input('lokasi');
    //     $device->save();

    //     return redirect()->route('devices.index')->with('success', 'Device berhasil diupdate!');
    // }

    // // Menghapus device dari database
    // public function destroy($id)
    // {
    //     $device = Device::find($id);
    //     $device->delete();

    //     return redirect()->route('devices.index')->with('success', 'Device berhasil dihapus!');
    // }
}
