<?php

// app/Http/Controllers/BillController.php

namespace App\Http\Controllers;

use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{
    /**
     * ════════════════════════════════════════════════════════════════
     * GET /api/bills
     * Mengambil semua riwayat tagihan beserta daftar pesertanya.
     * ════════════════════════════════════════════════════════════════
     */
    public function index(): JsonResponse
    {
        // with('participants') = Eager Loading
        // Artinya: ambil semua bills DAN langsung sertakan data participants-nya
        // dalam SATU query yang efisien (bukan N+1 query yang lambat).
        //
        // latest() = urutkan berdasarkan created_at DESC (tagihan terbaru di atas)
        $bills = Bill::with('participants')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'count' => $bills->count(),
            'data' => $bills,
        ]);
    }

    /**
     * ════════════════════════════════════════════════════════════════
     * POST /api/bills
     * Menyimpan tagihan baru beserta semua pesertanya.
     * Menggunakan DB Transaction untuk menjamin integritas data.
     * ════════════════════════════════════════════════════════════════
     */
    public function store(Request $request): JsonResponse
    {
        // ── LANGKAH 1: VALIDASI ──────────────────────────────────────────────
        //
        // $request->validate() akan OTOMATIS mengembalikan response JSON 422
        // (Unprocessable Entity) jika ada aturan yang dilanggar — SELAMA
        // request memiliki header "Accept: application/json".
        // Kamu tidak perlu menulis kode return-error sendiri untuk validasi.
        //
        $request->validate([
            // Data Tagihan Utama
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',
            'service_fee' => 'nullable|numeric|min:0',
            'packaging_fee' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0',
            'split_method' => 'required|in:rata,custom',

            // Data Peserta (array of objects)
            'participants' => 'required|array|min:1',
            'participants.*.name' => 'required|string|max:100',
            'participants.*.amount_due' => 'required|numeric|min:0',
        ]);

        // ── LANGKAH 2: DATABASE TRANSACTION ─────────────────────────────────
        //
        // MASALAH TANPA TRANSACTION:
        //   Bayangkan Bill::create() berhasil, tapi createMany() gagal
        //   di tengah jalan (misal: data ke-3 dari 5 peserta error).
        //   Hasilnya: bill tersimpan tapi participants-nya tidak lengkap → data cacat!
        //
        // SOLUSI DENGAN TRANSACTION:
        //   Semua operasi database di dalam closure dianggap SATU unit kerja.
        //   Jika ada yang gagal → ROLLBACK (semua dibatalkan, database kembali bersih).
        //   Jika semua berhasil → COMMIT (semua perubahan disimpan permanen).
        //   Inilah prinsip ATOMICITY dalam ACID database.
        //
        try {
            $bill = DB::transaction(function () use ($request) {

                // Simpan data tagihan utama ke tabel bills
                // '?? 0' = jika nilai null (tidak dikirim Frontend), gunakan 0
                $bill = Bill::create([
                    'subtotal' => $request->subtotal,
                    'discount' => $request->discount ?? 0,
                    'delivery_fee' => $request->delivery_fee ?? 0,
                    'service_fee' => $request->service_fee ?? 0,
                    'packaging_fee' => $request->packaging_fee ?? 0,
                    'tax_amount' => $request->tax_amount ?? 0,
                    'grand_total' => $request->grand_total,
                    'split_method' => $request->split_method,
                ]);

                // Simpan semua peserta sekaligus (bulk insert — jauh lebih efisien)
                // createMany() secara OTOMATIS menambahkan bill_id ke setiap record peserta
                // sehingga kita tidak perlu menulisnya secara manual di setiap item.
                $bill->participants()->createMany($request->participants);

                // load('participants') = ambil ulang data participants dari database
                // dan lampirkan ke objek $bill agar bisa langsung di-return ke Frontend
                return $bill->load('participants');
            });

            // Jika transaction berhasil → return 201 Created
            return response()->json([
                'success' => true,
                'message' => 'Tagihan berhasil disimpan.',
                'data' => $bill,
            ], 201);

        } catch (\Exception $e) {
            // Jika ada exception apapun → transaction otomatis di-ROLLBACK
            // Kita hanya perlu mengembalikan response error yang informatif

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan tagihan. Silakan coba lagi.',
                // Tampilkan detail error hanya di mode debug (development)
                // Di production (APP_DEBUG=false), nilai ini akan null
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}