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
        $data['log'] = DB::table('devices')->select('id','nama','no_sn','ip_address','online')->orderBy('online', 'DESC')->get();
        return view('devices.index',$data);
    }

    
    public function FingerLog(Request $request)
    {
        // Redirect legacy route to new system logs
        return redirect()->route('admin.logs', ['type' => 'finger']);
    }

    public function DeviceLog(Request $request)
    {
        // Redirect legacy route to new system logs
        return redirect()->route('admin.logs', ['type' => 'device']);
    }

    public function SystemLogs(Request $request)
    {
        $type = $request->input('type', 'finger'); // 'finger' or 'device'
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $sn = $request->input('sn');
        $search = $request->input('search');

        // Get devices for filter dropdown
        $devices = DB::table('devices')->select('nama', 'no_sn')->get();

        if ($type === 'device') {
            $query = DB::table('device_log');
            $data['lable'] = "Device Handshake Logs";
        } else {
            $query = DB::table('finger_log');
            $data['lable'] = "Finger Raw Data Logs";
        }

        // Apply Filters
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        } elseif ($startDate) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        } elseif ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        if ($sn) {
            if ($type === 'device') {
                $query->where('sn', $sn);
            } else {
                $query->where('url', 'like', '%' . $sn . '%');
            }
        }

        if ($search) {
            $query->where('data', 'like', '%' . $search . '%');
        }

        // Pagination
        $logs = $query->orderBy('id', 'DESC')
            ->paginate(20)
            ->withQueryString();

        return view('admin.system-logs', compact('logs', 'devices', 'type'));
    }
    public function Attendance(Request $request) {
        // Get list of devices for filter
        $devices = DB::table('devices')->select('nama', 'no_sn')->get();

        $query = DB::table('attendances')
            ->leftJoin('devices', 'attendances.sn', '=', 'devices.no_sn')
            ->select('attendances.*', 'devices.nama as device_name');

        // Filter by Date
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate && $endDate) {
            $query->whereBetween('attendances.timestamp', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        } elseif ($startDate) {
            $query->where('attendances.timestamp', '>=', $startDate . ' 00:00:00');
        } elseif ($endDate) {
            $query->where('attendances.timestamp', '<=', $endDate . ' 23:59:59');
        }

        // Filter by Device SN
        if ($request->filled('sn')) {
            $query->where('attendances.sn', $request->input('sn'));
        }

        $attendances = $query->orderBy('attendances.timestamp', 'DESC')
            ->orderBy('attendances.id', 'DESC') // Secondary sort for stability
            ->paginate(15)
            ->withQueryString(); // Keep filters in pagination links

        return view('devices.attendance', compact('attendances', 'devices'));
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

    /**
     * Test connection to device by pinging its IP
     */
    public function pingDevice(Request $request, $id)
    {
        $device = DB::table('devices')->where('id', $id)->first();
        
        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Device not found'], 404);
        }
        
        if (empty($device->ip_address)) {
            return response()->json([
                'success' => false, 
                'message' => 'IP address not available. Device has not connected yet.',
                'device' => $device->no_sn
            ]);
        }
        
        // Ping the device (2 packets, 2 second timeout)
        $output = [];
        $returnCode = 0;
        
        // Cross-platform ping command
        if (PHP_OS === 'WINNT') {
            exec("ping -n 2 -w 2000 " . escapeshellarg($device->ip_address) . " 2>&1", $output, $returnCode);
        } else {
            exec("ping -c 2 -W 2 " . escapeshellarg($device->ip_address) . " 2>&1", $output, $returnCode);
        }
        
        $pingSuccess = ($returnCode === 0);
        $outputText = implode("\n", $output);
        
        // Calculate last seen
        $lastSeen = $device->online ? \Carbon\Carbon::parse($device->online)->diffForHumans() : 'Never';
        
        return response()->json([
            'success' => $pingSuccess,
            'device' => [
                'serial' => $device->no_sn,
                'name' => $device->nama,
                'ip' => $device->ip_address,
                'last_seen' => $lastSeen,
                'online_at' => $device->online
            ],
            'ping_result' => $pingSuccess ? 'Device is reachable' : 'Device is not reachable',
            'details' => $outputText
        ]);
    }
}
