<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * OrderStats
 * ------------------------------------------------------------------
 * Menghitung ringkasan pesanan dari dokumen Firestore (koleksi "orders")
 * untuk Dashboard & Laporan Penjualan admin.
 *
 * Bentuk dokumen mengikuti public/js/acicu-firebase.js:
 *  - items[]        : { name, qty, price }
 *  - total          : int
 *  - paymentStatus  : menunggu | berhasil | gagal | refund
 *  - createdAt      : string ISO-8601
 *  - recipient.nama : string
 */
class OrderStats
{
    public const CONFIRMED = 'berhasil';

    /**
     * @param  array<int, array<string, mixed>>  $orders
     * @return array{
     *     total: int,
     *     today: int,
     *     porsi: int,
     *     revenue: int,
     *     confirmed: int,
     *     bestSeller: ?string,
     *     bestSellerQty: int,
     *     trend: array<int, array{label: string, porsi: int, nominal: int}>,
     *     recent: array<int, array<string, mixed>>
     * }
     */
    public static function summarize(array $orders): array
    {
        $today = Carbon::today()->toDateString();

        $total = count($orders);
        $todayCount = 0;
        $porsi = 0;
        $revenue = 0;
        $confirmed = 0;
        $seller = [];
        $trend = [];

        // 7 hari terakhir (termasuk hari ini).
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $trend[$date] = ['label' => $date, 'porsi' => 0, 'nominal' => 0];
        }

        foreach ($orders as $order) {
            $status = (string) ($order['paymentStatus'] ?? 'menunggu');
            $orderPorsi = self::porsi($order);
            $porsi += $orderPorsi;

            $date = self::dateOf($order);

            if ($date === $today) {
                $todayCount++;
            }

            if (isset($trend[$date])) {
                $trend[$date]['porsi'] += $orderPorsi;
                $trend[$date]['nominal'] += (int) ($order['total'] ?? 0);
            }

            foreach (($order['items'] ?? []) as $item) {
                $name = (string) ($item['name'] ?? '');
                if ($name === '') {
                    continue;
                }
                $seller[$name] = ($seller[$name] ?? 0) + (int) ($item['qty'] ?? 0);
            }

            if ($status === self::CONFIRMED) {
                $confirmed++;
                $revenue += (int) ($order['total'] ?? 0);
            }
        }

        $bestSeller = null;
        $bestSellerQty = 0;
        if ($seller !== []) {
            arsort($seller);
            $bestSeller = (string) array_key_first($seller);
            $bestSellerQty = (int) reset($seller);
        }

        return [
            'total' => $total,
            'today' => $todayCount,
            'porsi' => $porsi,
            'revenue' => $revenue,
            'confirmed' => $confirmed,
            'bestSeller' => $bestSeller,
            'bestSellerQty' => $bestSellerQty,
            'trend' => array_values($trend),
            'recent' => array_slice(array_values($orders), 0, 5),
        ];
    }

    /**
     * Total porsi (jumlah qty) dalam satu pesanan.
     *
     * @param  array<string, mixed>  $order
     */
    public static function porsi(array $order): int
    {
        $porsi = 0;
        foreach (($order['items'] ?? []) as $item) {
            $porsi += (int) ($item['qty'] ?? 0);
        }

        return $porsi;
    }

    /**
     * Tanggal (Y-m-d) pesanan dibuat.
     *
     * @param  array<string, mixed>  $order
     */
    public static function dateOf(array $order): string
    {
        $createdAt = (string) ($order['createdAt'] ?? '');

        if ($createdAt === '') {
            return '';
        }

        try {
            return Carbon::parse($createdAt)->toDateString();
        } catch (\Throwable) {
            return substr($createdAt, 0, 10);
        }
    }

    /**
     * Ringkas rincian item menjadi teks, mis. "2× Ciweng, 1× Cilukba".
     *
     * @param  array<string, mixed>  $order
     */
    public static function itemsLabel(array $order): string
    {
        $parts = [];
        foreach (($order['items'] ?? []) as $item) {
            $name = (string) ($item['name'] ?? '-');
            if (! empty($item['variant'])) {
                $name .= ' ('.$item['variant'].')';
            }
            $parts[] = ((int) ($item['qty'] ?? 0)).'× '.$name;
        }

        return implode(', ', $parts);
    }
}
