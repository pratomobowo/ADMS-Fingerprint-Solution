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
        $data['log'] = DB::table('devices')->select('id','nama','no_sn','online')->orderBy('online', 'DESC')->get();
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
       $attendances = DB::table('attendances')
           ->leftJoin('devices', 'attendances.sn', '=', 'devices.no_sn')
           ->select('attendances.*', 'devices.nama as device_name')
           ->orderBy('attendances.id','DESC')
           ->paginate(15);

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

    /**
     * Update device alias/name
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
        ]);

        DB::table('devices')->where('id', $id)->update([
            'nama' => $request->nama,
            'updated_at' => now(),
        ]);

        return redirect()->route('devices.index')
            ->with('success', 'Nama perangkat berhasil diperbarui.');
    }

    /**
     * Show secret manual attendance form
     */
    public function manualAttendance()
    {
        $devices = DB::table('devices')->select('no_sn', 'nama')->get();
        return view('devices.manual-attendance', compact('devices'));
    }

    /**
     * Store manual attendance record
     */
    public function storeManualAttendance(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|string|max:50',
            'sn' => 'required|string',
            'date' => 'required|date',
            'time' => 'required',
        ]);

        $timestamp = $request->date . ' ' . $request->time . ':00';

        DB::table('attendances')->insert([
            'sn' => $request->sn,
            'table' => 'ATTLOG',
            'stamp' => '9999',
            'employee_id' => $request->employee_id,
            'timestamp' => $timestamp,
            'status1' => $request->status1 ?? 0,
            'status2' => $request->status2 ?? 0,
            'status3' => $request->status3 ?? 0,
            'status4' => $request->status4 ?? 0,
            'status5' => $request->status5 ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('manual.attendance')
            ->with('success', 'Absensi manual berhasil ditambahkan untuk ID: ' . $request->employee_id);
    }


    // // Menghapus device dari database
    // public function destroy($id)
    // {
    //     $device = Device::find($id);
    //     $device->delete();

    //     return redirect()->route('devices.index')->with('success', 'Device berhasil dihapus!');
    // }
}
