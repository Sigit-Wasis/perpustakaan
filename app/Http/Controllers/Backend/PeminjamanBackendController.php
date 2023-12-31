<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamanBackendController extends Controller
{
    public function index()
    {
        $peminjaman = DB::table('transaksi')
            ->select(
                'transaksi.*',
                'peminjam.nama'
            )
            ->join('peminjam', 'peminjam.id', 'transaksi.id_peminjam')
            ->orderBy('transaksi.id', 'DESC')
            ->paginate(5);

        return view('backend.peminjaman.index', compact('peminjaman'));
    }

    public function pengembalian($id_transaksi)
    {
        // Dapatkan informasi transaksi dan detail transaksi
        $transaksi = DB::table('transaksi')->find($id_transaksi);
        $detailTransaksis = DB::table('detail_transakasi')->where('id_transaksi', $id_transaksi)->get();

        // Validasi ID transaksi
        if (!$transaksi || $detailTransaksis->isEmpty()) {
            return redirect()->route('backend-index-transaksi')->with('error', 'Transaksi tidak ditemukan atau detail transaksi kosong.');
        }

        // Hitung jumlah hari keterlambatan
        $tanggalKembali = strtotime(Carbon::parse($transaksi->tanggal_kembali));
        $tanggalPengembalian = strtotime(Carbon::now()->toDateString());

        // Hitung denda (5000 per hari terlambat)
        $jarak = $tanggalPengembalian - $tanggalKembali;
        $telatHari = $jarak / 60 / 60 / 24;
        $denda = $telatHari * 5000;

        // Hitung jumlah total buku yang dikembalikan
        $totalBukuDikembalikan = $transaksi->total;

        // Update detail transaksi dengan informasi telat_pengembalian dan denda
        DB::table('detail_transakasi')->where('id_transaksi', $id_transaksi)->update([
            'telat_pengembalian' => max(0, $telatHari), // Telat Pengembalian (hari)
            'denda' => $denda, // Jumlah denda
            'updated_at' => now(),
        ]);

        // Kurangkan stok buku dengan jumlah buku yang dipinjam
        DB::table('buku')->where('id', $detailTransaksis->first()->id_buku)->increment('stok_buku', (int) $totalBukuDikembalikan);

        DB::table('transaksi')->where('id', $id_transaksi)->update([
            'status' => 1, // 0 : BELUM DI KEMBALIKAN 1 : SUDAH DIKEMBALIKAN
        ]);

        return redirect()->route('backend-index-transaksi')->with('message', 'Buku berhasil dikembalikan.');
    }
}
