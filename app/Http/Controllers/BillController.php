<?php

// app/Http/Controllers/BillController.php

namespace App\Http\Controllers;

use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        $bills = auth()->user()->bills()->with('participants')
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
        // Jika participants dikirim sebagai string JSON (misal dari FormData multipart/form-data),
        // kita perlu men-decode-nya menjadi array sebelum divalidasi.
        if ($request->has('participants') && is_string($request->participants)) {
            $request->merge([
                'participants' => json_decode($request->participants, true)
            ]);
        }
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

            // Data Metode Pembayaran
            'payment_type' => 'nullable|string|in:rekening,qr',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'qr_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // Maksimal 5MB


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
        // Handle upload gambar QR jika ada (dan jika payment_type = qr)
        $qrImagePath = null;
        if ($request->hasFile('qr_image')) {
            // Simpan gambar ke folder 'qris' di dalam storage public
            $qrImagePath = $request->file('qr_image')->store('qris', 'public');
        }

        try {
            $bill = DB::transaction(function () use ($request, $qrImagePath) {


                // Simpan data tagihan utama ke tabel bills
                // '?? 0' = jika nilai null (tidak dikirim Frontend), gunakan 0
                $bill = Bill::create([
                    'user_id' => auth()->id(),
                    'subtotal' => $request->subtotal,
                    'discount' => $request->discount ?? 0,
                    'delivery_fee' => $request->delivery_fee ?? 0,
                    'service_fee' => $request->service_fee ?? 0,
                    'packaging_fee' => $request->packaging_fee ?? 0,
                    'tax_amount' => $request->tax_amount ?? 0,
                    'grand_total' => $request->grand_total,
                    'split_method' => $request->split_method,
                    'payment_type' => $request->payment_type,
                    'bank_name' => $request->bank_name,
                    'account_number' => $request->account_number,
                    'account_name' => $request->account_name,
                    'qr_image_path' => $qrImagePath,
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

    /**
     * ════════════════════════════════════════════════════════════════
     * GET /api/bills/{id}
     * Menampilkan detail satu tagihan spesifik berdasarkan ID.
     * Digunakan untuk fitur Scan QR Code oleh Front-End.
     * ════════════════════════════════════════════════════════════════
     */
    public function show($id): JsonResponse
    {
        try {
            // with('participants') memuat relasi agar rincian utuh
            // findOrFail($id) akan memicu ModelNotFoundException jika ID tidak ada
            $bill = auth()->user()->bills()->with('participants')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $bill,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Tangani error jika ID tidak ditemukan dengan response JSON 404
            return response()->json([
                'success' => false,
                'message' => 'Tagihan tidak ditemukan.',
            ], 404);
        }
    }

    // --- FUNGSI UNTUK MENGHAPUS SEMUA TAGIHAN ---
    public function deleteAll(): JsonResponse
    {
        // Langsung ambil semua tagihan dari database milik user login
        $bills = auth()->user()->bills()->get();

        foreach ($bills as $bill) {
            // 1. Hapus file gambar QRIS jika ada di storage
            if ($bill->qr_image_path && Storage::disk('public')->exists($bill->qr_image_path)) {
                Storage::disk('public')->delete($bill->qr_image_path);
            }

            // 2. Hapus data partisipan biar MySQL nggak error
            $bill->participants()->delete();

            // 3. Hapus tagihan utama
            $bill->delete();
        }

        return response()->json(['message' => 'Semua riwayat berhasil dihapus'], 200);
    }

    // --- FUNGSI UNTUK MENGHAPUS TAGIHAN ---
    public function destroy($id)
    {
        // 1. Cari tagihan berdasarkan ID milik user login
        $bill = auth()->user()->bills()->find($id);

        if (!$bill) {
            return response()->json(['message' => 'Tagihan tidak ditemukan atau Anda tidak memiliki akses'], 404);
        }

        // 2. Hapus dulu anak-anaknya (Partisipan) biar MySQL gak marah
        $bill->participants()->delete();

        // 3. Hapus induknya (Tagihannya)
        $bill->delete();

        // 4. Kasih tau Frontend kalau sukses
        return response()->json(['message' => 'Tagihan berhasil dihapus permanen!'], 200);
    }
}